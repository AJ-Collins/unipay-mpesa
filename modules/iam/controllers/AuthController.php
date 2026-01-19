<?php

namespace iam\controllers;

use Yii;
use iam\models\static\auth\Login;
use helpers\auth\jwt\BlacklistModel;
use iam\models\static\auth\Register;
use helpers\auth\jwt\RefreshTokenModel;
use iam\models\static\auth\ResetPassword;
use iam\models\static\auth\ChangePassword;
use iam\models\static\auth\ActivateAccount;
use iam\models\static\auth\Me;
use iam\models\static\auth\RequestPasswordReset;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="Available endpoints for user authentication"
 * )
 */
class AuthController extends \helpers\Controller
{

	public function actionRegister()
	{
		$model = new Register();
		$dataRequest = Yii::$app->request->getBodyParams();
		$model->load(['Register' => $dataRequest]);
		if ($model->register()) {
			return $this->alertifyResponse([
				'statusCode' => 200,
				'message'    => 'Registration successful. Please check your email to verify your account before logging in.',
				'type'       => 'alert',
				'theme'      => 'success',
			]);
		}

		return $this->errorResponse($model->getErrors());
	}
	public function actionActivate()
	{
		$token = Yii::$app->request->get('token');

		$model = new ActivateAccount();
		$model->token = $token;

		if ($model->activate()) {
			// Success — show message (for web) or return JSON (for API)
			return $this->alertifyResponse([
				'statusCode' => 200,
				'message'    => 'Your account has been successfully activated! You can now log in.',
				'type'       => 'toast',
				'theme'      => 'success',
				'redirect'   => '/auth/login' // frontend can redirect
			]);
		}
		// Failed
		return $this->alertifyResponse([
			'statusCode' => 400,
			'message'    => $model->getErrorMessage(),
			'type'       => 'alert',
			'theme'      => 'danger'
		]);
	}
	public function actionLogin()
	{
		$model = new Login();
		$dataRequest = Yii::$app->request->getBodyParams();
		// Load and attempt login
		if ($model->load(['Login' => $dataRequest]) && $model->login()) {
			return $this->payloadResponse($this->generateTokens(Yii::$app->user->id), [
				'statusCode' => 200,
				'message' => 'Access granted',
				'type' => 'toast'
			]);
		}
		return $this->errorResponse($model->getErrors());
	}
	public function actionMe()
	{
		$user = Yii::$app->user->identity;
		return Me::findIdentity($user->user_id);
		return $this->payloadResponse(Me::findIdentity($user->user_id), [
			'statusCode' => 200,
		]);
	}
	public function actionRefresh()
	{
		$refreshToken = Yii::$app->request->cookies->getValue('refresh_token');
		if (!$refreshToken) {
			return $this->errorResponse(401, false, 'No refresh token');
		}
		$rt = RefreshTokenModel::findValid($refreshToken);
		if (!$rt) {
			// Invalid/expired → delete cookie
			Yii::$app->response->cookies->remove('refresh_token');
			return $this->errorResponse(401, false, 'Invalid refresh token');
		}
		// Revoke old one
		$rt->is_revoked = true;
		$rt->save(false);
		//return $this->generateTokens($rt->user_id); // creates new cookie
		return $this->payloadResponse($this->generateTokens($rt->user_id), [
			'statusCode' => 200,
		]);
	}
	public function actionRequestPasswordReset()
	{
		$model = new RequestPasswordReset();
		$dataRequest = Yii::$app->request->getBodyParams();
		if ($model->load(['RequestPasswordReset' => $dataRequest]) && $model->resetToken()) {
			return $this->alertifyResponse([
				'statusCode' => 200,
				'message'    => 'A password reset link has been sent to your email address.',
				'type'       => 'alert',
				'theme'      => 'success'
			]);
		}
		return $this->errorResponse($model->getErrors());
	}
	public function actionResetPassword()
	{
		$model = new ResetPassword();
		$dataRequest = Yii::$app->request->getBodyParams();
		$model->load(['ResetPassword' => $dataRequest]);
		$model->token = Yii::$app->request->getQueryParam('token');
		if ($model->reset()) {
			return $this->alertifyResponse([
				'statusCode' => 200,
				'message'    => 'Your password has been reset successfully. Please log in with your new password.',
				'type'       => 'alert',
				'theme'      => 'success',
				'forceLogout' => true
			]);
		}
		return $this->errorResponse($model->getErrors());
	}
	public function actionChangePassword()
	{
		$model = new ChangePassword();
		$dataRequest = Yii::$app->request->getBodyParams();
		$model->load($model->load(['ChangePassword' => $dataRequest]));
		if ($model->change()) {
			//  Delete the HttpOnly refresh token cookie
			Yii::$app->response->cookies->remove('refresh_token');
			return $this->alertifyResponse([
				'statusCode' => 200,
				'message' => 'Password changed successfully. You have been logged out from all devices.',
				'type' => 'alert',
				'theme'      => 'success',
				'forceLogout' => true
			]);
		}
		// Validation failed
		return $this->errorResponse($model->getErrors());
	}
	public function actionLogout()
	{
		$user = Yii::$app->user->identity;
		if (!$user) {
			return $this->alertifyResponse([
				'statusCode' => 200,
				'message'    => 'You are already logged out.',
				'type'       => 'toast',
				'theme'      => 'info',
				'forceLogout' => true
			]);
		}
		// Extract current jti from the incoming access token
		$currentJti = null;
		$authHeader = Yii::$app->request->headers->get('Authorization');
		if ($authHeader && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
			try {
				$token = Yii::$app->jwt->parser()->parse($m[1]);
				$currentJti = $token->claims()->get('jti');
			} catch (\Throwable) {
				// Invalid token → treat as already logged out
			}
		}
		// Parse request
		$model = new class extends \yii\base\Model {
			/**
			 *@OA\Schema(
			 *  schema="Logout",
			 *  @OA\Property(property="device", type="string",title="Device", example="current", description="Specify 'current' to logout from current device only, or 'all' to logout from all devices."),
			 * )
			 */
			public $device = 'current';
			public function rules()
			{
				return [['device', 'in', 'range' => ['current', 'all']]];
			}
		};
		$model->load(Yii::$app->request->getBodyParams(), '');

		$logoutAll = ($model->device === 'all');

		if ($logoutAll) {
			$this->logoutFromAllDevices($user->user_id);
			$message = 'Logged out from all devices.';
		} else {
			$this->logoutFromCurrentDevice();
			$message = 'Logged out successfully.';
		}

		// BLACKLIST THE CURRENT ACCESS TOKEN IMMEDIATELY
		if ($currentJti) {
			BlacklistModel::add($currentJti, time() + 3600);
		}
		// DESTROY YII USER IDENTITY
		Yii::$app->user->logout(false);
		// DELETE REFRESH COOKIE
		Yii::$app->response->cookies->remove('refresh_token');
		return $this->alertifyResponse([
			'statusCode' => 200,
			'message'    => $message,
			'type'       => 'toast',
			'theme'      => 'success',
			'forceLogout' => true
		]);
	}
	private function logoutFromCurrentDevice(): void
	{
		$refreshToken = Yii::$app->request->cookies->getValue('refresh_token');
		if ($refreshToken) {
			$rt = RefreshTokenModel::findValid($refreshToken);
			if ($rt) {
				$rt->is_revoked = true;
				$rt->save(false);
				// Blacklist the jti of the associated access token
				BlacklistModel::add($rt->jti, $rt->expires_at);
			}
		}
	}
	private function logoutFromAllDevices(int $userId): void
	{
		// Revoke ALL refresh tokens
		RefreshTokenModel::updateAll(
			['is_revoked' => true],
			['user_id' => $userId]
		);
		//  Blacklist ALL jti from active refresh tokens
		$activeTokens = RefreshTokenModel::find()
			->select(['jti', 'expires_at'])
			->where(['user_id' => $userId])
			->all();
		foreach ($activeTokens as $rt) {
			BlacklistModel::add($rt->jti, $rt->expires_at);
		}
	}
	private function generateTokens(int $userId)
	{
		// Your beautiful JwtConfig component
		$jwt = Yii::$app->jwtConfiguration; // → helpers\auth\JwtConfig

		// Always use UTC for JWTs (prevents clock skew issues)
		$now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
		$jti = bin2hex(random_bytes(16)); // Unique ID for this token

		// Access token: expires in 1 hour (or use params)
		$accessExpires = $now->modify('+' . (Yii::$app->params['jwtAccessTokenTtl'] ?? 3600) . ' seconds');

		// Build the access token
		$token = $jwt->builder()
			->relatedTo((string) $userId)                    // sub = user ID
			->identifiedBy($jti)                             // jti = token ID (for blacklist)
			->issuedAt($now)                                 // iat
			->expiresAt($accessExpires)                      // exp
			->issuedBy(Yii::$app->request->getHostInfo())    // iss
			->permittedFor(Yii::$app->request->getHostInfo()) // aud
			->withClaim('roles', array_keys(Yii::$app->authManager->getRolesByUser($userId)))
			->withClaim('name', Yii::$app->user->identity->profile?->first_name ?? 'User')
			->getToken($jwt->signer(), $jwt->signingKey());

		$accessToken = $token->toString();

		// Generate refresh token + store in DB
		$refreshExpires = time() + (Yii::$app->params['jwtRefreshTokenTtl'] ?? 7776000); // 90 days
		$refreshRecord = RefreshTokenModel::create(
			$userId,
			$jti,
			$refreshExpires
		);

		// Set HttpOnly, Secure refresh token cookie (XSS-proof)
		Yii::$app->response->cookies->add(new \yii\web\Cookie([
			'name'     => 'refresh_token',
			'value'    => $refreshRecord->token,
			'expire'   => $refreshExpires,
			'httpOnly' => true,
			'secure'   => !YII_ENV_DEV,           // HTTPS only in production
			'sameSite' => \yii\web\Cookie::SAME_SITE_LAX,
			'path'     => '/',
		]));

		// Return only access token in JSON (refresh is in cookie)
		return [
			'access_token' => $accessToken,
			'expires_in'   => Yii::$app->params['jwtAccessTokenTtl'] ?? 3600,
			'token_type'   => 'Bearer',
		];
	}
}
