<?php

namespace admin\controllers;

use Yii;
use helpers\log\AccessLogModel;
use admin\models\searches\AccessLogSearch;

/**
 * @OA\Tag(
 *     name="Access Logs",
 *     description="Available endpoints for Access Logs"
 * )
 */
class AccessLogController extends \helpers\Controller
{
    public $permissions = [
        'adminAccessLogList' => 'View Multiple AccessLogModel Records',
        'adminAccessLogView' => 'View Single AccessLogModel Record',
    ];
    public function actionIndex()
    {
        Yii::$app->user->can('adminAccessLogList');
        $searchModel = new AccessLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->payloadResponse($dataProvider, ['oneRecord' => false]);
    }

    public function actionView($id)
    {
        Yii::$app->user->can('adminAccessLogView');
        return $this->payloadResponse($this->findModel($id));
    }
    protected function findModel($access_id)
    {
        if (($model = AccessLogModel::findOne(['access_id' => $access_id])) !== null) {
            return $model;
        }
        throw new \yii\web\NotFoundHttpException('The requested accesslogmodel does not exist');
    }
}
