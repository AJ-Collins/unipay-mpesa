<?php

namespace iam\controllers;

use Yii;
use iam\hooks\AuthHelper;
use iam\hooks\AuthConfigs;
use yii\web\NotFoundHttpException;

/**
 * @OA\Tag(
 *     name="User Management",
 *     description="Available endpoints for user management"
 * )
 */
class UserController extends \helpers\Controller
{
    public $permissions = [
        'iamUsers' => 'Manage System Users',
        'iamUserView' => 'View System Users',
        'iamUserCreate' => 'Create System Users',
        'iamUserDelete' => 'Delete System Users',
        'iamUserRestore' => 'Restore Deleted Users',
        'iamUserAssign' => 'Add User to Group',
        'iamUserRemove' => 'Remove User from Group',
        'iamUserChangeStatus' => 'Change User Status',
    ];
    public function actionIndex()
    {
        Yii::$app->user->can('iamUsers');
        $searchModel = new \iam\models\searches\UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->payloadResponse($dataProvider, ['oneRecord' => false]);
    }
    public function actionView($id)
    {
        Yii::$app->user->can('iamUsers');
        return $this->payloadResponse($this->findModel($id));
    }
    public function actionCreate()
    {
        Yii::$app->user->can('iamUserCreate');
        // Get current body params (from POST JSON or form data)
        $bodyParams = Yii::$app->request->getBodyParams();
        // Add or override your custom parameter
        //$bodyParams['created_by'] = Yii::$app->user->id; 
        // Set the modified body params back
        Yii::$app->request->setBodyParams($bodyParams);
        // Run the register action from AuthController
        return Yii::$app->runAction('iam/auth/register');
    }
    public function actionToggleDelete($id)
    {

        $request = Yii::$app->request;
        $protectedUsers = (new Yii::$app->user->identityClass)->protectedUsers ?? [];
        if (in_array($id, $protectedUsers) && $request->getMethod() === 'DELETE') {
            return $this->errorResponse(403, false, 'This user is protected and cannot be deleted.');
        }
        $model = $this->findModel($id);
        // Determine action based on HTTP method
        if ($request->getMethod() === 'DELETE') {
            Yii::$app->user->can('iamUserDelete');
            // Soft Delete
            if ($model->is_deleted) {
                return $this->errorResponse(404, false, 'The requested user does not exist or is already deleted.');
            }
            $model->is_deleted = 1;
            $message = 'User deleted successfully.';
        } elseif ($request->getMethod() === 'PATCH') {
            Yii::$app->user->can('iamUserRestore');
            // Restore
            if (!$model->is_deleted) {
                return $this->errorResponse(400, false, 'The user is not deleted.');
            }
            $model->is_deleted = 0;
            $message = 'User restored successfully.';
        } else {
            return $this->errorResponse(405, false, 'Method not allowed. Use DELETE or PATCH.');
        }
        // Save the change
        if ($model->save(false)) {
            return $this->alertifyResponse([
                'statusCode' => 202,
                'message'    => $message,
                'type'       => 'alert',
                'theme'      => 'success',
            ]);
        }
    }
    public function actionAssign($username, $id = null)
    {
        Yii::$app->user->can('iamUserAssign');
        $auth = AuthConfigs::authManager();
        $group = $auth->getGroup($id);

        if (!$group) {
            return $this->errorResponse(404, false, 'The requested group does not exist.');
        }
        $uid = Yii::$app->user->identityClass::findOne(['username' => $username])->getId();

        // Check if user already has the group
        if ($auth->getAssignment($group->name, $uid)) {
            return $this->alertifyResponse([
                'statusCode' => 200,
                'message'    => 'User already belongs to ' . $group->description . '.',
                'type'       => 'toast',
                'theme'      => 'info',
            ]);
        }

        if ($auth->assign($group, $uid)) {
            return $this->alertifyResponse([
                'statusCode' => 202,
                'message'    => 'User added to ' . $group->description . ' successfully.',
                'type'       => 'alert',
                'theme'      => 'success',
            ]);
        }
        AuthHelper::invalidate();
    }
    public function actionRemove($username, $id)
    {
        Yii::$app->user->can('iamUserRemove');

        $auth = AuthConfigs::authManager();
        $group = $auth->getGroup($id);
        if (!$group) {
            return $this->errorResponse(404, false, 'The requested group does not exist.');
        }
        $user = Yii::$app->user->identityClass::findOne(['username' => $username]);
        if (!$user) {
            return $this->errorResponse(404, false, 'The requested user does not exist.');
        }
        $uid = $user->getId();
        // Check if assignment exists
        if (!$auth->getAssignment($group->name, $uid)) {
            return $this->alertifyResponse([
                'statusCode' => 200,
                'message'    => 'User does not belong to ' . $group->description . '.',
                'type'       => 'toast',
                'theme'      => 'info',
            ]);
        }
        // Revoke assignment
        if ($auth->revoke($group, $uid)) {
            AuthHelper::invalidate();
            return $this->alertifyResponse([
                'statusCode' => 202,
                'message'    => 'User removed from ' . $group->description . ' successfully.',
                'type'       => 'alert',
                'theme'      => 'success',
            ]);
        }

        return $this->errorResponse(500, false, 'Failed to remove user from the group.');
    }
    public function actionBan($username)
    {
        Yii::$app->user->can('iamUserRemove');
        $user = $this->findModel($username);
        if (!$user) {
            return $this->errorResponse(404, false, 'The requested user does not exist.');
        }
        if ($user->status == Yii::$app->user->identityClass::STATUS_BANNED) {
            return $this->alertifyResponse([
                'statusCode' => 200,
                'message'    => 'User is already banned.',
                'type'       => 'toast',
                'theme'      => 'info',
            ]);
        }
        if ($user->status != Yii::$app->user->identityClass::STATUS_ACTIVE) {
            return $this->errorResponse(400, false, 'Only active users can be banned.');
        }
        $auth = AuthConfigs::authManager();

        if ($auth->revokeAll($user->getId())) {
            AuthHelper::invalidate();
            $user->status = Yii::$app->user->identityClass::STATUS_BANNED;
            $user->save(false);
            return $this->alertifyResponse([
                'statusCode' => 202,
                'message'    => 'User banned successfully and all permissions revoked.',
                'type'       => 'alert',
                'theme'      => 'success',
            ]);
        }
        return $this->errorResponse(500, false, 'Failed to ban the user.');
    }
    public function actionToggleStatus($username)
    {
        Yii::$app->user->can('iamUserChangeStatus');
        $model = $this->findModel($username);

        if ($model->is_deleted) {
            return $this->errorResponse(400, false, 'Cannot change status of a deleted user.');
        }
        $bannedStatus = Yii::$app->user->identityClass::STATUS_BANNED;
        $inactiveStatus = Yii::$app->user->identityClass::STATUS_INACTIVE;
        $activeStatus = Yii::$app->user->identityClass::STATUS_ACTIVE;
        // If banned, activate the user
        if ($model->status == $bannedStatus) {
            $newStatus = $activeStatus;
            $wasBanned = true;
        } elseif ($model->email_verified_at === null && $model->status == $inactiveStatus) {
            return $this->errorResponse(400, false, 'Cannot change status of a user with unverified email.');
        } else {
            $newStatus = $model->status == $inactiveStatus ? $activeStatus : $inactiveStatus;
            $wasBanned = false;
        }
        $model->status = $newStatus;
        if ($model->save(false)) {
            // If user was banned and is now being activated, add to default user group
            if ($wasBanned && $newStatus == $activeStatus) {
                $auth = AuthConfigs::authManager();
                $usrGroup = method_exists($auth, 'getGroup') ? $auth->getGroup(Yii::$app->user->identityClass::DEFAULT_GROUP) : null;
                if ($usrGroup !== null) {
                    $auth->assign($usrGroup, $model->user_id);
                    AuthHelper::invalidate();
                } else {
                    Yii::warning("Default 'user' group not found; skipping assignment for username: {$model->username}");
                }
            }
            $statusLabel = $newStatus == $activeStatus ? 'activated' : 'deactivated';
            return $this->alertifyResponse([
                'statusCode' => 202,
                'message'    => 'User ' . $statusLabel . ' successfully.',
                'type'       => 'alert',
                'theme'      => 'success',
            ]);
        }
    }
    protected function findModel($id)
    {
        if (($user = Yii::$app->user->identityClass::findOne(['username' => $id])) !== null) {
            return $user;
        } else {
            throw new NotFoundHttpException('The requested user does not exist.');
        }
    }
}
