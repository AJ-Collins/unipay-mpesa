<?php

namespace helpers\behaviors;

use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use Yii;

class Creator extends AttributeBehavior
{
    public $attribute = 'created_by';

    // Default value if no user
    public $value = 0; // Guest/anonymous

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'setValue',
        ];
    }

    public function setValue($event)
    {
        $model = $event->sender;
        $attribute = $this->attribute;

        if ($model->hasAttribute($attribute)) {
            // Always set – override if already set
            $model->$attribute = Yii::$app->user->identity ? Yii::$app->user->identity->user_id : $this->value;

            Yii::debug("Behavior set {$attribute} = " . $model->$attribute, __CLASS__);
        }
    }
}