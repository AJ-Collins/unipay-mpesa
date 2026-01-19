<?php

namespace admin\controllers;

use Yii;
use helpers\log\LogModel;
use admin\models\searches\ErrorLogSearch;

/**
 * @OA\Tag(
 *     name="Error Logs",
 *     description="Available endpoints for LogModel model"
 * )
 */
class ErrorLogController extends \helpers\Controller
{
    public $permissions = [
        'adminErrorLogList' => 'View Multiple LogModel Records',
        'adminErrorLogView' => 'View Single LogModel Record',
        'adminErrorLogResolve' => 'Delete LogModel Record',
    ];
    public function actionIndex()
    {
        Yii::$app->user->can('adminErrorLogList');
        $searchModel = new ErrorLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->payloadResponse($dataProvider, ['oneRecord' => false]);
    }

    public function actionView($id)
    {
        Yii::$app->user->can('adminErrorLogView');
        return $this->payloadResponse($this->findModel($id));
    }

    public function actionResolve($id)
    {
        Yii::$app->user->can('adminErrorLogResolve');
        $model = $this->findModel($id);
        if ($model->is_resolved == true) {
            throw new \yii\web\NotFoundHttpException('The requested error does not exist or is already resolved');
        }
        $model->is_resolved = true;
        $model->save();
        return $this->alertifyResponse([
            'statusCode' => 202,
            'message'    => 'Error resolved successfully.',
            'theme'      => 'success',
            'type'       => 'alert'
        ]);
    }

    protected function findModel($id)
    {
        if (($model = LogModel::findOne(['id' => $id])) !== null) {
            return $model;
        }
        throw new \yii\web\NotFoundHttpException('The requested logmodel does not exist');
    }
}
