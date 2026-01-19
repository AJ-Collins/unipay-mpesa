<?php

namespace iam\controllers;

use Yii;


class RoleController extends \iam\hooks\AccessController
{
    public $permissions = [
        'iamRoleAdd' => 'Create System Roles',
        'iamRoleEdit' => 'Update System Roles',
        'iamRoleDelete' => 'Delete System Roles',
        'iamRoleAssign' => 'Assign System Roles',
        'iamRoleRemove' => 'Remove System Roles',
        'iamRoleGetUsers' => 'Get Users with System Roles',
    ];
    public function actionAssign($id)
    {
        Yii::$app->user->can('iam' . $this->label . 'Assign');
        $request = Yii::$app->request->bodyParams;
        if (!isset($request['permissions']) || !is_array($request['permissions'])) {
            return $this->errorResponse(422, false, 'Invalid or missing permissions.');
        }
        $model = $this->findModel($id);
        $assignedCount = $model->addChildren($request['permissions']);
        if ($assignedCount > 0) {
            return $this->payloadResponse($model->getItems(), [
                'statusCode' => 202,
                'type' => 'toast',
                'message' => "{$assignedCount} permission(s) assigned successfully."
            ]);
        }
        // No new assignments were made
        return $this->payloadResponse($model->getItems(), [
            'statusCode' => 202,
            'theme' => 'info',
            'type' => 'toast',
            'message' => 'No new permissions were assigned.'
        ]);
    }
    public function actionRemove($id)
    {
        Yii::$app->user->can('iam' . $this->label . 'Remove');
        $request = Yii::$app->request->bodyParams;
        if (!isset($request['permissions']) || !is_array($request['permissions'])) {
            return $this->errorResponse(422, false, 'Invalid or missing permissions.');
        }
        $model = $this->findModel($id);
        $revokedCount = $model->removeChildren($request['permissions']);
        $message = $revokedCount > 0
            ? "{$revokedCount} permission(s) revoked successfully."
            : "No permissions were revoked.";

        return $this->payloadResponse($model->getItems(), [
            'statusCode' => 200,
            'message' => $message,
            'type' => 'toast',
            'theme' => $revokedCount > 0 ? 'success' : 'info',
        ]);
    }
    public function getType()
    {
        return \helpers\auth\Item::TYPE_ROLE;
    }
}
