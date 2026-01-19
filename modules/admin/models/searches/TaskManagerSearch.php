<?php

namespace admin\models\searches;

use helpers\data\ActiveDataProvider;
use helpers\cron\SchedulerModel;

/** 
 * @OA\Schema(
 *  schema="Scheduler",
 *  @OA\Property(property="task_title", type="string", title="Task Title", description="Title or name of the scheduled task", example="Email Notification",),
 *  @OA\Property(property="system_service", type="string", title="Service", description="The class name of the job to be executed", example="b661d846",),
 *  @OA\Property(property="is_recurring", type="boolean", title="Is Recurring", description="Indicates if the task is recurring. Its only available for interval schedule type", example=true,),   
 *  @OA\Property(property="schedule_type", type="string", title="Schedule Type", description="Type of schedule for the task, e.g., cron or interval", example="interval",),        
 *  @OA\Property(property="schedule_value", type="string", title="Schedule Value", description="Value associated with the schedule type, e.g., interval duration or cron expression", example="1",), 
 *  @OA\Property(property="service_payload", type="object", title="Payload", description="Additional data required for the job execution",
 *      @OA\Property(property="to", type="array", title="Recipient Email", description="Email addresses of the recipients", example={"douglasdaggs@gmail.com","douglasdaggs@tum.ac.ke"}, @OA\Items(type="string")),
 *      @OA\Property(property="cc", type="array", title="CC Email", description="Email addresses to be CC'd on the email", example={"anandadaggs@gmail.com","admin@crackit.co.ke"}, @OA\Items(type="string")),
 *      @OA\Property(property="subject", type="string", title="Email Subject", description="Subject of the email", example="Test Email from Omnibase",),
 *      @OA\Property(property="body", type="string", title="Email Body", description="Content of the email", example="This is a test email sent from the Omnibase system."),
 *      @OA\Property(property="template", type="string", title="Email Template", description="Template to be used for the email", example="default"),
 *    ),
 * )
 */

class TaskManagerSearch extends SchedulerModel
{
    public function rules()
    {
        return [
            [['task_id', 'is_recurring', 'next_run_at', 'status', 'created_at', 'updated_at'], 'integer'],
            [['task_title', 'payload', 'cron_expression'], 'safe'],
            [['q'], 'safe']
        ];
    }
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return \yii\base\Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $params[(new \ReflectionClass($this))->getShortName()] = $params;
        $model = new class extends SchedulerModel {};
        $query = $model::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['next_run_at' => SORT_DESC]]
        ]);
        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        if (!empty($this->q)) {
            //search logic
            $query->andFilterWhere(
                self::ciLikeAny(['task_title', 'job_class', 'payload', 'cron_expression'], $this->q)
            );
            return $dataProvider;
        }

        return $dataProvider;
    }
}
