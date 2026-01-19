<?php

namespace iam\hooks;

use Yii;
use helpers\auth\Item;
use yii\web\NotFoundHttpException;
use iam\models\static\rbac\AuthItem;
use iam\models\static\rbac\AuthItemSearch;
use yii\helpers\Inflector;

/**
 * @OA\Tag(
 *     name="Authorization",
 *     description="Available endpoints for user authorization"
 * )
 */
class AccessController extends \helpers\Controller
{
    public function actionIndex()
    {
        Yii::$app->user->can('iamAccessControl');
        $searchModel = new AuthItemSearch(['type' => $this->type]);
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());
        return $this->payloadResponse($dataProvider, ['oneRecord' => false]);
    }
    public function actionCreate()
    {
        Yii::$app->user->can('iam' . $this->label . 'Add');
        $request = Yii::$app->request->bodyParams;
        $key = strtolower($this->label); // e.g., "role"
        $model = new AuthItem();
        $model->type = $this->type;
        // Map frontend fields → model attributes
        $model->name = $request[$key . '_id'] ?? null;
        $model->description = $request[$key . '_name'] ?? null;
        $model->data = $request['description'] ?? null;
        if ($model->save()) {
            return $this->payloadResponse($model, [
                'statusCode' => 201,
                'message' => $this->label . ' created successfully',
                'type' => 'toast'
            ]);
        }
        return $this->errorResponse($model->getErrors());
    }
    public function actionUpdate($id)
    {
        Yii::$app->user->can('iam' . $this->label . 'Edit');
        $data = Yii::$app->request->getBodyParams();
        $data['data'] = $data['description'] ?? '';
        $data['description'] = $data[strtolower($this->label) . '_name'] ?? '';
        $data['ruleName'] = Inflector::camelize($data['ruleName'] ?? null);
        $dataRequest['AuthItem'] = $data;
        $model = $this->findModel($id);
        if ($model->load($dataRequest) && $model->save()) {
            return $this->payloadResponse($this->findModel($id), ['statusCode' => 202, 'message' => $this->label . ' updated successfully','type'=>'toast']);
        }
        return $this->errorResponse($model->getErrors());
    }
    public function actionView($id)
    {
        Yii::$app->user->can('iamAccessControllView');
        $model = $this->findModel($id);
        if ($this->type == Item::TYPE_PERMISSION) {
            return $this->payloadResponse($model);
        }
        $model = array_merge($model->toArray(), [
            'items' => $model->getItems(),
        ]);

        return $this->payloadResponse($model);
    }
    public function actionDelete($id)
    {
        Yii::$app->user->can('iam' . $this->label . 'Delete');
        $model = $this->findModel($id);
        AuthConfigs::authManager()->remove($model->item);
        AuthHelper::invalidate();
        return $this->alertifyResponse(['statusCode'=>200,'message'=>$this->label.' deleted successfully','type'=>'toast']);
    }
    public function getType() {}
    public function getLabel()
    {
        if ($this->type === Item::TYPE_ROLE) {
            return 'Role';
        } elseif ($this->type === Item::TYPE_PERMISSION) {
            return 'Permission';
        } else {
            return 'Group';
        }
    }
    protected function findModel($id)
    {
        $auth = AuthConfigs::authManager();
        // $item = $this->type === Item::TYPE_ROLE ? $auth->getRole($id) : $auth->getPermission($id);
        if ($this->type === Item::TYPE_ROLE) {
            $item = $auth->getRole($id);
        } elseif ($this->type === Item::TYPE_PERMISSION) {
            $item = $auth->getPermission($id);
        } else {
            $item = $auth->getGroup($id);
        }
        if ($item) {
            return new AuthItem($item);
        } else {
            throw new NotFoundHttpException("The requested " . strtolower($this->label) . " does not exist.");
        }
    }
}
