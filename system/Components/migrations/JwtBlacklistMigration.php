<?php
namespace helpers\migrations;
use helpers\Migration;

class JwtBlacklistMigration extends Migration
{
    protected function beginCommand($description)
    {
        return true;
    }
    protected function endCommJand($time)
    {
        return true;
    }
    public function safeUp()
    {
        $this->createTable('{{%jwt_blacklist}}', [
            'id' => $this->bigPrimaryKey(),
            'jti' => $this->string(128)->notNull()->unique(),
            'expires_at' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $this->tableOptions);

        $this->createIndex('idx-jwt_blacklist-expires', '{{%jwt_blacklist}}', 'expires_at');
    }

    public function safeDown()
    {
        $this->dropTable('jwt_blacklist');
    }
}
