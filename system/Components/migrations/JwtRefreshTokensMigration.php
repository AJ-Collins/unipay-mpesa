<?php
namespace helpers\migrations;
use helpers\Migration;

class JwtRefreshTokensMigration extends Migration
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
        
        $this->createTable('{{%jwt_refresh_tokens}}', [
            'id' => $this->bigPrimaryKey(),
            'user_id' => $this->bigInteger()->notNull(),
            'token' => $this->string(512)->notNull()->unique(),     // full refresh token (not hashed)
            'jti' => $this->string(128)->notNull()->unique(),       // JWT ID — prevents replay
            'expires_at' => $this->integer()->notNull(),
            'ip_address' => $this->string(45),
            'user_agent' => $this->text(),
            'is_revoked' => $this->boolean()->notNull()->defaultValue(false),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),

            'FOREIGN KEY ([[user_id]]) REFERENCES {{%users}} ([[user_id]])' .
                $this->buildFkClause('ON DELETE CASCADE', 'ON UPDATE CASCADE'),
        ], $this->tableOptions);

        $this->createIndex('idx-jwt_refresh-expires', '{{%jwt_refresh_tokens}}', 'expires_at');
        $this->createIndex('idx-jwt_refresh-jti', '{{%jwt_refresh_tokens}}', 'jti');
        $this->createIndex('idx-jwt_refresh-user', '{{%jwt_refresh_tokens}}', 'user_id');

       
    }

    public function safeDown()
    {
        $this->dropTable('jwt_blacklist');
    }
}
