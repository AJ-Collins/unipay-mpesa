<?php

namespace admin\models\searches;

use helpers\data\ActiveDataProvider;
use helpers\log\LogModel;
use Yii;

/**
 * @OA\Schema(
 * schema="LogModel",
 * @OA\Property(property="level", type="string", title="Level", description="Severity level of the error", example="Error",),
 * @OA\Property(property="category", type="string", title="Category", description="Category of the error", example="application",),
 * @OA\Property(property="log_time", type="string", format="date-time", title="Log Time", description="Timestamp when the error was logged", example="2024-04-27T12:34:56Z",),
 * @OA\Property(property="prefix", type="string", title="Prefix", description="Prefix information for the log entry", example="[error][application]",),
 * @OA\Property(property="message", type="string", title="Message", description="Detailed error message", example="An unexpected error occurred in the application.",),
 * @OA\Property(property="is_resolved", type="integer", title="Is Resolved", description="Indicates whether the error has been resolved", example="0",),
 * )
 */
class ErrorLogSearch extends LogModel
{
    public function rules()
    {
        return [
            [['id', 'level', 'is_resolved'], 'integer'],
            [['category', 'prefix', 'message'], 'safe'],
            [['log_time'], 'number'],
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
        $model = new class extends LogModel {
            public function fields()
            {
                return [
                    'id',
                    'level' => function ($model) {
                        return $model->levelName;
                    },
                    'category',
                    'log_time' => function ($model) {
                        return Yii::$app->settings->DateTime($model->log_time);
                    },
                    'is_resolved',
                ];
            }
        };
        $query = $model::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['is_resolved' => SORT_ASC, 'level' => SORT_ASC, 'log_time' => SORT_DESC,]],
        ]);
        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        if (!empty($this->q)) {
            //search logic
            $query->andFilterWhere(
                self::ciLikeAny(['category', 'prefix', 'message'], $this->q)
            );
            return $dataProvider;
        }
        //filter logic
        $query->andFilterWhere(self::ciLike('category', $this->category ?? ''))
            ->andFilterWhere(self::ciLike('prefix', $this->prefix ?? ''))
            ->andFilterWhere(self::ciLike('message', $this->message ?? ''));

        return $dataProvider;
    }
}
