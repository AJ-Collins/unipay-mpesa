<?php

namespace admin\controllers;

use Yii;
use Cron\CronExpression;
use yii\helpers\ArrayHelper;
use helpers\cron\SchedulerModel;
use admin\models\searches\TaskManagerSearch;

/**
 * @OA\Tag(
 *     name="Task Manager",
 *     description="Available endpoints for Task Manager model"
 * )
 */
class TaskManagerController extends \helpers\Controller
{
    public $permissions = [
        'adminTaskManagerList' => 'View all scheduled tasks',
        'adminTaskManagerCreate' => 'Add New Task',
        'adminTaskManagerView' => 'View Single Task',
        'adminTaskManagerUpdate' => 'Edit Task',
        'adminTaskManagerDelete' => 'Delete Task',
        'adminTaskManagerRestore' => 'Restore Task',
    ];
    public function actionIndex()
    {
        Yii::$app->user->can('adminTaskManagerList');
        $searchModel = new TaskManagerSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->payloadResponse($dataProvider, ['oneRecord' => false]);
    }
    public function actionServices()
    {
        Yii::$app->user->can('adminTaskManagerCreate') || Yii::$app->user->can('adminTaskManagerUpdate');
        $model = new SchedulerModel();
        $data = ArrayHelper::map($model->getAvailableServices(), 'key', 'label');
        return $this->payloadResponse($data);
    }
    public function actionCreate()
    {
        Yii::$app->user->can('adminTaskManagerCreate');
        $model = new SchedulerModel([
            'next_run_at' => time(),
            'is_recurring' => false,
        ]);
        $dataRequest['SchedulerModel'] = Yii::$app->request->getBodyParams();
        if ($model->load($dataRequest)) {
            $model->system_service = $model->getAvailableServices($model->system_service)['fullClass'] ?? null;
            $valid = $model->validate();
            $payloadErrors = [];
            $payloadValid = $model->validatePayload($model->service_payload, $payloadErrors);
            if (!empty($payloadErrors)) {
                $model->addError('system_service', $payloadErrors);
            }
            $valid = $valid && $payloadValid;
            if ($valid) {
                //$model->service_payload = $model->service_payload;
                if ($model->schedule_type === "interval") {
                    $model->schedule_value = ($model->schedule_value * 60); // Convert minutes to seconds
                    $model->next_run_at =  time() + (int) $model->schedule_value; // Schedule first run
                } else {
                    $cron = new CronExpression($model->schedule_value);
                    $model->next_run_at = $cron->getNextRunDate()->getTimestamp();
                    $model->is_recurring = true;
                }
                if ($model->system_service || class_exists($model->system_service)) {
                    $instance = new $model->system_service();
                    $property = [];
                    if (property_exists($instance, 'dataObject')) {
                        $property = (array)($instance->dataObject ?? []);
                        $model->service_payload = array_merge(array_diff_key($model->service_payload, array_flip($property['keys'] ?? [])), [$property['name'] ?? '' => array_intersect_key($model->service_payload, array_flip($property['keys'] ?? []))]);
                    }
                }
                if ($model->save(false)) {
                    return $this->payloadResponse($model, ['statusCode' => 201, 'message' => 'Task scheduled successfully']);
                }
            }
            return $this->errorResponse($model->getErrors());
        }
    }
    public function actionServiceFields($id)
    {
        Yii::$app->user->can('adminTaskManagerCreate') || Yii::$app->user->can('adminTaskManagerUpdate');
        $id = (new SchedulerModel())->getAvailableServices($id)['fullClass'] ?? null;
        try {
            $model = new SchedulerModel(['system_service' => $id]);
            $fields = $model->getPayloadFields();
            if (empty($fields)) {
                return $this->alertifyResponse([
                    'statusCode' => 204,
                    'message'    => 'No payload fields defined for this Job.',
                    'theme'      => 'error',
                    'type'       => 'alert'
                ]);
            }
            return $this->payloadResponse($fields);
        } catch (\Throwable $e) {
            Yii::error("Error fetching payload fields for job class {$id}: " . $e->getMessage(), __METHOD__);
            return $this->alertifyResponse([
                'statusCode' => 500,
                'message'    => 'Error fetching payload fields: ' . $e->getMessage(),
                'theme'      => 'error',
                'type'       => 'alert'
            ]);
        }
    }
    public function actionView($id)
    {
        Yii::$app->user->can('adminTaskManagerView');
        return $this->payloadResponse($this->findModel($id));
    }

    public function actionToggleDelete($id)
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;
        // Soft Delete (DELETE method)
        if ($request->isDelete) {
            Yii::$app->user->can('adminTaskManagerDelete');
            if ($model->is_deleted == true) {
                throw new \yii\web\NotFoundHttpException('The requested task does not exist or is already deleted');
            }
            $model->delete();
            return $this->alertifyResponse([
                'statusCode' => 202,
                'message'    => 'Task deleted successfully.',
                'theme'      => 'success',
                'type'       => 'alert'
            ]);
        }
        // Restore (PATCH method)
        if ($request->isPatch) {
            Yii::$app->user->can('adminTaskManagerRestore');
            if ($model->is_deleted == false) {
                throw new \yii\web\NotFoundHttpException('The requested task is not deleted');
            }
            $model->restore();
            return $this->alertifyResponse([
                'statusCode' => 202,
                'message'    => 'Task restored successfully.',
                'theme'      => 'success',
                'type'       => 'alert'
            ]);
        }
        // Invalid method
        throw new \yii\web\MethodNotAllowedHttpException('Only DELETE and PATCH methods are allowed.');
    }

    protected function findModel($task_id)
    {

        if (($model = SchedulerModel::findOne(['task_id' => $task_id])) !== null) {
            return $model;
        }
        throw new \yii\web\NotFoundHttpException('The requested schedulermodel does not exist');
    }
}
