<?php

namespace helpers\log;

use yii\log\DbTarget as Target;

class DbTarget extends Target
{
    public $logTable = '{{%system_log}}';

    public function init()
    {
        parent::init();
        if ($this->db->schema->getTableSchema($this->logTable) === null) {
            $migration = new \helpers\migrations\LogTableMigration(['logTable' => $this->logTable]);
            $migration->up();;
        }
    }
    

}
