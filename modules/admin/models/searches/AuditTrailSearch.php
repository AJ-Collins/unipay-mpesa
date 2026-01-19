<?php

namespace admin\models\searches;

use helpers\BaseModel as Model;
use helpers\data\ActiveDataProvider;
use helpers\audit\AuditModel;

/**
 * @OA\Schema(
 *  schema="Audit Trail List",
 *  @OA\Property(property="audit_id", type="integer", title="Audit ID", description="Unique identifier of the audit record", example=1,),
 *  @OA\Property(property="user", type="string", title="System User", description="Name of the system user who performed the action", example="John Doe",),
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
 *  @OA\Property(property="field_name", type="string", title="Field Name", description="Name of the field that was changed", example="email",),
 *  @OA\Property(property="old_value", type="string", title="Old Value", description="Previous value of the field before the change", example="old@example.com",),
 *  @OA\Property(property="new_value", type="string", title="New Value", description="New value of the field after the change", example="new@example.com",),
 *  @OA\Property(property="audit_time", type="string", format="date-time", title="Audit Time", description="Timestamp when the audit record was created", example="2024-01-01 12:00:00",),
 *  @OA\Property(property="operation", type="string", title="Operation", description="Type of operation performed", example="UPDATE",),
 *  @OA\Property(property="request_method", type="string", title="Request Method", description="HTTP method used for the request", example="POST",),
 * )
 */
/**
 * @OA\Schema(
 *  schema="Audit Trail",
 *  @OA\Property(property="user", type="string", title="System User", description="Name of the system user who performed the action", example="John Doe",),
 *  @OA\Property(property="ip_info", type="object", title="IP Information", description="Details about the IP address from which the action was performed",
 *      @OA\Property(property="ip_address", type="string", title="IP Address", example="41.89.128.3"),
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
 * @OA\Property(property="request_context", type="object", title="Request Context", description="Details about the request context",
 *      @OA\Property(property="request_route", type="string", title="Request Route", example="admin/user/update"),
 *      @OA\Property(property="headers", type="object", title="Headers", example={"Content-Type":"application/json","Accept":"application/json"},),
 *      @OA\Property(property="query_params", type="object", title="Query Parameters", example={"search":"john","status":"active"},),
 *      @OA\Property(property="body_params", type="object", title="Body Parameters", description="Parameters sent in the body of the request",
 *          @OA\Property(property="email", type="string", example="john@example.com"),
 *          @OA\Property(property="password", type="string", example="secret"),
 *      ),
 *      @OA\Property(property="url", type="string", title="URL", example="https://api.example.com/v1/admin/user/update"),
 * ),
 *  @OA\Property(property="field_name", type="string", title="Field Name", description="Name of the field that was changed", example="email",),
 *  @OA\Property(property="old_value", type="string", title="Old Value", description="Previous value of the field before the change", example="old@example.com",),
 *  @OA\Property(property="new_value", type="string", title="New Value", description="New value of the field after the change", example="new@example.com",),
 *  @OA\Property(property="audit_time", type="string", format="date-time", title="Audit Time", description="Timestamp when the audit record was created", example="2024-01-01 12:00:00",),
 *  @OA\Property(property="operation", type="string", title="Operation", description="Type of operation performed", example="UPDATE",),
 *  @OA\Property(property="process_time", type="string", title="Process Time", description="Time taken to process the request", example="150 ms",),
 *  @OA\Property(property="memory_used", type="string", title="Memory Used", description="Amount of memory used during the request", example="1.5 MB",),
 * )
 */
class AuditTrailSearch extends Model
{
    public function rules()
    {
        return [
            [['q'], 'safe']
        ];
    }
    public function scenarios()
    {
        return Model::scenarios();
    }
    public function search($params)
    {
        $params[(new \ReflectionClass($this))->getShortName()] = $params;
        $model = new class extends AuditModel {
            public function fields()
            {
                return [
                    'audit_id' => 'id',
                    'user' => function ($model) {
                        return $model->user_id == 'NO_USER_ID' ? 'System' : \Yii::$app->user->identityClass::findOne($model->user_id)->profile->full_name;
                    },
                    'ip_info' => function ($model) {
                        return (new \helpers\IPinfo())->getInfo($model->ip_address);
                    },
                    'field_name',
                    'old_value',
                    'new_value',
                    'audit_time' => function ($model) {
                        return \Yii::$app->settings->DateTime($model->audit_time);
                    },
                    'operation',
                    'request_method',
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
            $query->andWhere(self::ciLikeAny(['model_name','operation','request_method','field_name','old_value','new_value','user_id','request_route','headers','query_params','body_params','raw_body','url','ip_address','user_agent'],$this->q));
            return $dataProvider;
        }


        return $dataProvider;
    }
}
