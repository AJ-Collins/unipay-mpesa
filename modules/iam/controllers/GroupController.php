<?php

namespace iam\controllers;

use Yii;

class GroupController extends \iam\hooks\AccessController
{
    public $permissions = [
        'iamAccessControl' => 'Manage Access Control Features',
        'iamAccessControllView' => 'View Access Control Information',
        'iamGroupAdd' => 'Create System Groups',
        'iamGroupEdit' => 'Update System Groups',
        'iamGroupDelete' => 'Delete System Groups',
        'iamGroupAssign' => 'Assign System Groups',
        'iamGroupRemove' => 'Remove System Groups',
    ];
    public function actionAssign($id)
    {
        Yii::$app->user->can('iam' . $this->label . 'Assign');
        $request = Yii::$app->request->bodyParams;
        if (!isset($request['roles']) || !is_array($request['roles'])) {
            return $this->errorResponse(422, false, 'Invalid or missing roles.');
        }
        $model = $this->findModel($id);
        $assignedCount = $model->addChildren($request['roles']);
        if ($assignedCount > 0) {
            return $this->payloadResponse($model->getItems(), [
                'statusCode' => 202,
                'type' => 'toast',
                'message' => "{$assignedCount} role(s) assigned successfully."
            ]);
        }
        // No new assignments were made
        return $this->payloadResponse($model->getItems(), [
            'statusCode' => 202,
            'theme' => 'info',
            'type' => 'toast',
            'message' => 'No new roles were assigned.'
        ]);
    }
    public function actionRemove($id)
    {
        Yii::$app->user->can('iam' . $this->label . 'Remove');
        $request = Yii::$app->request->bodyParams;
        if (!isset($request['roles']) || !is_array($request['roles'])) {
            return $this->errorResponse(422, false, 'Invalid or missing roles.');
        }
        $model = $this->findModel($id);
        $revokedCount = $model->removeChildren($request['roles']);
        $message = $revokedCount > 0
            ? "{$revokedCount} role(s) revoked successfully."
            : "No roles were revoked.";

        return $this->payloadResponse($model->getItems(), [
            'statusCode' => 200,
            'message' => $message,
            'type' => 'toast',
            'theme' => $revokedCount > 0 ? 'success' : 'info',
        ]);
    }
    public function actionAssignd($id)
    {
        Yii::$app->user->can('iamGroupAssign');
        $items = Yii::$app->request->getBodyParams();
        $model = $this->findModel($id);
        $success = $model->addChildren($items['roles']);
        if ($success > 0) {
            return $this->payloadResponse($model->getItems(), ['statusCode' => 202, 'message' => $success . ' roles assigned successfully']);
        }
        return $this->alertifyResponse(['statusCode' => 400, 'message' => 'Bad Request', 'theme' => 'warning', 'type' => 'toast']);
    }
    public function actionRemoved($id)
    {
        Yii::$app->user->can('iamGroupRemove');
        $items = Yii::$app->request->getBodyParams();
        $model = $this->findModel($id);
        $success = $model->removeChildren($items['roles']);
        if ($success > 0) {
            return $this->payloadResponse($model->getItems(), ['statusCode' => 202, 'message' => $success . ' roles removed successfully']);
        }
        return $this->alertifyResponse(['statusCode' => 400, 'message' => 'Bad Request', 'theme' => 'warning', 'type' => 'toast']);
    }
    public function getType()
    {
        return \helpers\auth\Item::TYPE_GROUP;
    }
}
