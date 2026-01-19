<?php

namespace admin\models\searches;

use helpers\audit\AuditModel;
use helpers\data\ActiveDataProvider;
use helpers\log\AccessLogModel;


/**
 * @OA\Schema(
 *  schema="AccessLog",
 *  @OA\Property(property="user", type="string", title="User", description="User who performed the action", example="System Admin",),
 *  @OA\Property(property="action", type="string", title="Action", description="The action performed by the user", example="login",),
 *  @OA\Property(property="description", type="string", title="Description", description="Detailed description of the access event", example="User logged in successfully",),
 *  @OA\Property(property="extra_data", type="string", title="Extra Data", description="Additional data related to the access event in JSON format", example="{}",),
 *  @OA\Property(property="ip_info", type="object", title="IP Information", description="Details about the IP address from which the action was performed",
 *      @OA\Property(property="ip_address", type="string", title="IP Address", example="192.168.1.1"),
 *      @OA\Property(property="country", type="string", title="Country", example="Kenya"),
 *      @OA\Property(property="country_code", type="string", title="Country Code", example="KE"),
 *      @OA\Property(property="region_code", type="string", title="Region Code", example="30"),
 *      @OA\Property(property="region_name", type="string", title="Region Name", example="Nairobi County"),
 *      @OA\Property(property="city", type="string", title="City", example="Nairobi"),
 *      @OA\Property(property="zip_code", type="string", title="ZIP Code", example="09831"),
 *      @OA\Property(property="latitude", type="number", format="float", title="Latitude", example=-1.2841),
 *      @OA\Property(property="longitude", type="number", format="float", title="Longitude", example=36.8155),
 *      @OA\Property(property="time_zone", type="string", title="Time Zone", example="Africa/Nairobi"),
 *      @OA\Property(property="isp", type="string", title="ISP", example="KENET"),
 *      @OA\Property(property="organization", type="string", title="Organization", example="Technical University of Mombasa"),
 *      @OA\Property(property="autonomous_system", type="string", title="Autonomous System", example="AS36914 Kenya Education Network"),
 * ),
 * @OA\Property(property="user_agent", type="object", title="User Agent", description="Details about the user agent from which the action was performed",
 *      @OA\Property(property="platform", type="string", title="Platform", example="Windows"),
 *      @OA\Property(property="browser", type="string", title="Browser", example="Chrome"),
 *      @OA\Property(property="browser_version", type="string", title="Browser Version", example="89.0.4389.82"),
 * ),
 * @OA\Property(property="access_time", type="string", format="date-time", title="Access Time", description="Timestamp of when the access occurred", example="2024-04-27T12:34:56Z"),
 * )
 */
class AccessLogSearch extends AccessLogModel
{
    public function rules()
    {
        return [
            [['access_id', 'user_id', 'is_deleted', 'created_at', 'updated_at'], 'integer'],
            [['action', 'description', 'extra_data', 'ip_address', 'user_agent'], 'safe'],
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
        $model = new class extends AccessLogModel {
            public function fields()
            {
                return [
                    'access_id',
                    'user' => function ($model) {
                        return $model->user_id == 'NO_USER_ID' ? 'System' : \Yii::$app->user->identityClass::findOne($model->user_id)->profile->full_name;
                    },
                    'description',
                    'ip_info' => function ($model) {
                        return (new \helpers\IPinfo())->getInfo($model->ip_address);
                    },
                    'user_agent' => function ($model) {
                        return (new AuditModel(['user_agent' => $model->user_agent]))->getUserAgent();
                    },
                    'access_time' => function ($model) {
                        return \Yii::$app->settings->DateTime($model->created_at);
                    },
                ];
            }
        };
        $query = $model::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        if (!empty($this->q)) {
            //search logic
            $query->andFilterWhere(
                self::ciLikeAny(['action', 'description', 'extra_data', 'ip_address', 'user_agent'], $this->q)
            );
            return $dataProvider;
        }
        //filter logic
        $query->andFilterWhere(self::ciLike('action', $this->action ?? ''))
            ->andFilterWhere(self::ciLike('description', $this->description ?? ''))
            ->andFilterWhere(self::ciLike('extra_data', $this->extra_data ?? ''))
            ->andFilterWhere(self::ciLike('ip_address', $this->ip_address ?? ''))
            ->andFilterWhere(self::ciLike('user_agent', $this->user_agent ?? ''));
        return $dataProvider;
    }
}
