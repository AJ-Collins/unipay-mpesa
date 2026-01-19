<?php

namespace iam\models\static\rbac;

use Yii;
use yii\base\Model;
use iam\hooks\AuthConfigs;
use helpers\auth\Item;
use yii\data\ArrayDataProvider;

class AuthItemSearch extends Model
{

    public $name;
    public $type;
    public $description;
    public $ruleName;
    public $data;
    public $q;


    public function rules()
    {
        return [
            [['name', 'ruleName', 'description', 'q'], 'safe'],
            [['type'], 'integer'],
        ];
    }

    public function search($params)
    {
        /* @var \yii\rbac\Manager $authManager */
        $authManager = AuthConfigs::authManager();

        if ($this->type == Item::TYPE_GROUP) {
            $items = $authManager->getGroups();
        } elseif ($this->type == Item::TYPE_ROLE) {
            $items = $authManager->getRoles();
        } else {
            $items = $authManager->getPermissions();
        }
        $this->load($params);
        $items = array_map(function ($item) {
            $isPermission = ($item->type === Item::TYPE_PERMISSION);
            $label = $isPermission ? 'permission' : ($item->type === Item::TYPE_ROLE ? 'role' : 'group');
            return (object) [
                $label.'_id'        => $item->name,
                $label.'_name' => $item->description,
                'description'        => is_null($item->data) ? '' : $item->data,
                'ruleName'    => $item->ruleName ?? '--no rule attached--',
                'protected'   => $isPermission
                    ? true
                    : in_array(
                        $item->name,
                        array_keys(array_merge((AuthConfigs::authManager())->protectedRoles['role'], (AuthConfigs::authManager())->protectedRoles['group']))
                    ),
            ];
        }, array_values($items));
        // If you want protected items always first by default
        usort($items, function ($a, $b) {
            return $b->protected <=> $a->protected; // true first
        });
        return new ArrayDataProvider([
            'allModels'  => $items,
            'pagination' => [
                'defaultPageSize' => \Yii::$app->params['defaultPageSize'],
                'pageSizeLimit'   => [1, \Yii::$app->params['pageSizeLimit']],
            ],
            'sort' => [
                'attributes' => ['name', 'description', 'type', 'ruleName', 'protected'],
                'defaultOrder' => ['protected' => SORT_ASC], // optional
            ],
        ]);
    }
}
