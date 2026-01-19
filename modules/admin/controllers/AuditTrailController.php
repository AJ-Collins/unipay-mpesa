<?php

namespace admin\controllers;

use Yii;
use helpers\audit\AuditModel;
use admin\models\searches\AuditTrailSearch;

/**
 * @OA\Tag(
 *     name="Audit Trail",
 *     description="Available endpoints for Audit Trail management"
 * )
 */
class AuditTrailController extends \helpers\Controller
{
    public $permissions = [
        'adminAuditTrailList' => 'View Multiple Audit Records',
        'adminAuditTrailView' => 'View Single Audit Record',
        'adminAuditTrailDelete' => 'Delete Audit Record',
        'adminAuditTrailRestore' => 'Restore Audit Record',
    ];
    public function actionIndex()
    {
        Yii::$app->user->can('adminAuditTrailList');
        $searchModel = new AuditTrailSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->payloadResponse($dataProvider, ['oneRecord' => false]);
    }
    public function actionView($uid)
    {
        Yii::$app->user->can('adminAuditTrailView');
        return $this->payloadResponse($this->findModel($uid));
    }

    public function actionToggleDelete($uid)
    {
        $model = $this->findModel($uid);
        $request = Yii::$app->request;
        // Soft Delete (DELETE method)
        if ($request->isDelete) {
            Yii::$app->user->can('adminAuditTrailDelete');
            if ($model->is_deleted == true) {
                throw new \yii\web\NotFoundHttpException('The requested audit record does not exist or is already deleted');
            }
            $model->is_deleted = true;
            $model->save(false);
            return $this->alertifyResponse([
                'statusCode' => 202,
                'message'    => 'Audit record deleted successfully.',
                'theme'      => 'success',
                'type'       => 'alert'
            ]);
        }
        // Restore (PATCH method)
        if ($request->isPatch) {
            Yii::$app->user->can('adminAuditTrailRestore');
            if ($model->is_deleted == false) {
                throw new \yii\web\NotFoundHttpException('The requested audit record is not deleted');
            }
            $model->is_deleted = false;
            $model->save(false);
            return $this->alertifyResponse([
                'statusCode' => 202,
                'message'    => 'Audit record restored successfully.',
                'theme'      => 'success',
                'type'       => 'alert'
            ]);
        }
        // Invalid method
        throw new \yii\web\MethodNotAllowedHttpException('Only DELETE and PATCH methods are allowed.');
    }

    protected function findModel($uid)
    {
        if (($model = AuditModel::findOne($uid)) !== null) {
            return $model;
        }
        throw new \yii\web\NotFoundHttpException('The requested audit record does not exist');
    }
}
