<?php

namespace helpers\migrations;

use helpers\Migration;

/**
 * Migration for creating a settings table.
 * @author Ananda Douglas <douglasdaggs@gmail.com>
 * @since 1.0.0
 */
class SystemSettingsMigration extends Migration
{
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
        $this->createTable('{{%system_settings}}', [
            'key' => $this->string(100)->notNull(),
            'category' => $this->string(20)->notNull()->defaultValue('GENERAL'),
            'disposition' => $this->integer()->notNull(),
            'current_value' => $this->text(),
            'default_value' => $this->text()->notNull(),
            'salt' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'PRIMARY KEY ([[key]])',
        ], $this->tableOptions);
    }
    public function safeDown()
    {
        $this->dropTable('{{%system_settings}}');
    }
}
