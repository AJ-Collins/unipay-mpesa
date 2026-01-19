<?php

namespace iam\controllers;

use Yii;
use yii\db\Query;
use helpers\Controller;
use iam\hooks\AuthHelper;
use yii\web\NotFoundHttpException;
use iam\models\static\rbac\AuthRules;
use iam\models\static\rbac\AuthRuleSearch;
use yii\helpers\ArrayHelper;

/**
 * RuleController - Manages RBAC Rules
 *
 * Provides CRUD operations for authorization rules in the RBAC system.
 * Handles listing, viewing, and updating of authorization rules.
 *
 * @author Ananda Douglas <douglasdaggs@gmail.com>
 */
class RuleController extends Controller
{
    /**
     * @var array Permission mappings for this controller
     */
    public $permissions = [
        'iamRuleManage' => 'Manage RBAC Rules',
    ];

    /**
     * Lists all AuthRule models.
     * 
     * @return array Payload response containing data provider with list of rules
     */
    public function actionIndex()
    {
        Yii::$app->user->can('iamRuleManage');
        $searchModel = new AuthRuleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->payloadResponse($dataProvider, ['oneRecord' => false]);
    }

    /**
     * Manages a specific AuthRule model.
     * 
     * Handles GET requests to retrieve rule details and PUT requests to update rule data.
     * Invalidates auth cache after successful updates.
     *
     * @param string $id The primary key of the rule to manage
     * @return array Payload response with rule data or success/error message
     * @throws NotFoundHttpException if the rule is not found
     */
    public function actionManage($id)
    {
        Yii::$app->user->can('iamRuleManage');
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->getMethod() === 'GET') {
            return $this->payloadResponse($model);
        }

        if ($request->getMethod() === 'PUT') {
            $dataRequest = $request->getBodyParams();
            $model->load(['AuthRules' => $dataRequest]);
            if ($model->save(false)) {
                AuthHelper::invalidate();
                return $this->alertifyResponse([
                    'statusCode' => 202,
                    'message'    => 'Rule updated successfully.',
                    'type'       => 'toast',
                    'theme'      => 'success',
                ]);
            }
            return $this->errorResponse($model->getErrors());
        }
    }
    public function actionList()
    {
        $q = Yii::$app->request->getQueryParam('q');
        $model = (new AuthRules())->dropdown($q);
        return $this->payloadResponse($model);
    }

    /**
     * Finds the AuthRules model based on its primary key value.
     * 
     * @param string $id The primary key value of the rule
     * @return AuthRules The loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AuthRules::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested rule does not exist.');
    }
}
