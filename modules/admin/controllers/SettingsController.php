<?php

namespace admin\controllers;

use Yii;
use yii\web\NotFoundHttpException;

/**
 * @OA\Tag(
 *     name="System Settings",
 *     description="Manage system settings"
 * )
 */

class SettingsController extends \helpers\Controller
{
    public $permissions = [
        'adminSettingsManage' => 'Manage System Settings',
    ];
    public function actionManage($id)
    {
        Yii::$app->user->can('adminSettingsManage');
        $modelName = '\admin\models\static\settings\\' . ucfirst($id);
        if (class_exists($modelName)) {
            $model = new $modelName;
            if (Yii::$app->request->getMethod() == 'POST') {
                if ($model->load([ucfirst($id) => Yii::$app->request->getBodyParams()])) {
                    //return $model;
                    if ($model->validate()) {
                        $model->saveAll();
                        Yii::$app->settings->invalidateCache();
                        return $this->payloadResponse($model, ['statusCode' => 201, 'message' => ucfirst($id) . ' settings updated successfully.','type'=>'toast']);
                    }
                    return $this->errorResponse($model->getErrors());
                }
            } elseif (Yii::$app->request->getMethod() == 'GET') {
                return $this->payloadResponse($model);
            }
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
    public function actionManageGeneral()
    {
        return $this->actionManage('general');
    }
    public function actionManageMailer()
    {
        return $this->actionManage('mailer');
    }
    public function actionManageSecurity()
    {
        return $this->actionManage('security');
    }
    public function actionManageTheme()
    {
        return $this->actionManage('theme');
    }
    
}
