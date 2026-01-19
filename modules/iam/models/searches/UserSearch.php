<?php

namespace iam\models\searches;

use yii\base\Model;
use iam\models\User;
use helpers\data\ActiveDataProvider;

/**
 * UserSearch represents the model behind the search form of `iam\models\User`.
 */
class UserSearch extends \helpers\BaseModel
{
    public $user_id;
    public $username;
    public $status;
    public $password_hash;
    public $q;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'status'], 'integer'],
            [['username', 'q'], 'safe'],
        ];
    }
    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $params[(new \ReflectionClass($this))->getShortName()] = $params;
        $model = new class extends User {
            public function fields()
            {
                return [
                    'username',
                    'status' => function () {
                        $status = $this->is_deleted ? self::STATUS_DELETED : $this->status;
                        return $this->loadStatus('SC' . $status);
                    },
                    'profile' => function () {
                        return $this->profile;
                    },
                ];
            }
        };
        $query = $model::find()->joinWith('profile'); // eager load profile if needed

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // Return empty result on invalid params
            $query->where('0=1');
            return $dataProvider;
        }
        // Global search with $q
        if (!empty($this->q)) {
            $query->andFilterWhere(
                self::ciLikeAny(['username', 'email_address', 'first_name', 'last_name', 'middle_name', 'mobile_number'], $this->q)
            );
        }
        return $dataProvider;
    }
}

