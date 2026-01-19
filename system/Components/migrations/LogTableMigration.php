<?php

namespace helpers\migrations;

use helpers\Migration;

/**
 * Migration for creating a log table.
 * @author Ananda Douglas <douglasdaggs@gmail.com>
 * @since 1.0.0
 */
class LogTableMigration extends Migration
{
    public $logTable;
    protected function beginCommand($description)
    {
        return true;
    }
    protected function endCommand($time)
    {
        return true;
    }
    public function safeUp()
    {
        if ($this->db->driverName === 'mysql') {
            // https://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $this->tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($this->logTable, [
            'id' => $this->bigPrimaryKey(),
            'level' => $this->integer(),
            'category' => $this->string(),
            'log_time' => $this->double(),
            'prefix' => $this->text(),
            'message' => $this->text(),
            'is_resolved' => $this->integer(2)->notNull()->defaultValue(0),
        ], $this->tableOptions);

        $this->createIndex('idx_log_level', $this->logTable, 'level');
        $this->createIndex('idx_log_category', $this->logTable, 'category');
    }

    public function down()
    {
        $this->dropTable($this->logTable);
    }
}
