<?php

namespace helpers\migrations;

use helpers\Migration;

class QueueMigration extends Migration
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
        // Create the db queue table using Yii2's default schema for yii\queue\db\Queue
        $this->createTable('{{%queue}}', [
            'id' => $this->bigPrimaryKey(),
            'channel' => $this->string()->notNull()->defaultValue('default'),
            'job' => $this->binary()->notNull(),
            'pushed_at' => $this->integer()->notNull(),
            'ttr' => $this->integer()->notNull(),
            'delay' => $this->integer()->notNull(),
            'priority' => $this->integer()->unsigned()->notNull()->defaultValue(1024),
            'reserved_at' => $this->integer(),
            'attempt' => $this->integer(),
            'done_at' => $this->integer(),
        ], $this->tableOptions);

        $this->createIndex('idx-queue-channel', '{{%queue}}', 'channel');
        $this->createIndex('idx-queue-reserved_at', '{{%queue}}', 'reserved_at');
        $this->createIndex('idx-queue-done_at', '{{%queue}}', 'done_at');
    }
    public function safeDown()
    {
        $this->dropTable('{{%queue}}');
    }
}
