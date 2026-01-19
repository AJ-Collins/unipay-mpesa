<?php

namespace helpers\audit;

use yii\base\Model;
use helpers\data\ActiveDataProvider;

class AuditSearchModel extends AuditModel
{

    public $q;
    public function rules()
    {
        return [
            [['audit_time', 'request_method', 'model_name', 'operation', 'field_name', 'old_value', 'new_value', 'user_id', 'ip_address','q'], 'safe'],
            [['old_value', 'duration', 'memory_max', 'request_route', 'new_value','user_agent'], 'string'],
        ];
    }
    public function scenarios()
    {
        return Model::scenarios();
    }
    public function search($params)
    {

        $query = self::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['audit_time' => SORT_DESC, 'created_at' => SORT_DESC,]],
        ]);
        if (isset($params['q'])) {
            $params[(new \ReflectionClass($this))->getShortName()]['q'] = $params['q'];
        }
        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }
        if (!empty($this->q)) {
            $query->andFilterWhere([
                'or',
                ['like', 'model_name', $this->q],
                ['like', 'operation', $this->q],
                ['like', 'field_name', $this->q],
                ['like', 'old_value', $this->q],
                ['like', 'new_value', $this->q],
                ['like', 'ip_address', $this->q],
                ['like', 'request_method', $this->q],
                ['like', 'request_route', $this->q],
                ['like', 'user_agent', $this->q],
                ['like', 'user_id', $this->q],
                ['like', 'url', $this->q],
                // add more searchable columns here
            ]);
        } else {
            $query->andFilterWhere(['user_id' => $this->user_id]);
            $query->andFilterWhere(['like', 'model_name', $this->model_name]);
            $query->andFilterWhere(['like', 'operation', $this->operation]);
            $query->andFilterWhere(['like', 'field_name', $this->field_name]);
            $query->andFilterWhere(['like', 'old_value', $this->old_value]);
            $query->andFilterWhere(['like', 'new_value', $this->new_value]);
            $query->andFilterWhere(['like', 'ip_address', $this->ip_address]);
            $query->andFilterWhere(['like', 'request_method', $this->request_method]);
            $query->andFilterWhere(['like', 'request_route', $this->request_route]);
            $query->andFilterWhere(['like', 'user_agent', $this->user_agent]);
            $query->andFilterWhere(['audit_time' => $this->audit_time]);
        }
        return $dataProvider;
    }
}
