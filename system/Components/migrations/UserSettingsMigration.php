<?php

namespace helpers\migrations;

use helpers\Migration;

/**
 * Migration for creating user settings table.
 * @author Ananda Douglas <douglasdaggs@gmail.com>
 * @since 1.0.0
 */
class UserSettingsMigration extends Migration
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
        $this->createTable('{{%user_settings}}', [
            'id' => $this->bigPrimaryKey(),
            'user_id' => $this->bigInteger()->notNull()->unique(),
            'settings' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'FOREIGN KEY ([[user_id]]) REFERENCES {{%users}} ([[user_id]])' .
                $this->buildFkClause('ON DELETE CASCADE', 'ON UPDATE CASCADE'),
        ], $this->tableOptions);
    }
    public function safeDown()
    {
        $this->dropTable('{{%user_settings}}');
    }
}
