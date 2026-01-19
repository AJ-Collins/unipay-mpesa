<?php

use helpers\Migration;

/**
 * Class m250304_102628_init
 */
class m250304_102628_init extends Migration
{
    public function up()
    {
        // 1. Profiles (public user data — safe to expose in JWT)
        $this->createTable('{{%profiles}}', [
            'profile_id' => $this->bigPrimaryKey(),
            'first_name' => $this->string(50)->notNull(),
            'middle_name' => $this->string(50),
            'last_name' => $this->string(50)->notNull(),
            'email_address' => $this->string(128)->notNull()->unique(),
            'mobile_number' => $this->string(15)->unique(),
            'avatar_url' => $this->string(255),
            'data' => $this->json(), // custom fields, preferences, etc.
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $this->tableOptions);

        // 2. Users — core identity (never expose password_hash or auth_key!)
        $this->createTable('{{%users}}', [
            'user_id' => $this->bigPrimaryKey(),
            'username' => $this->string(64)->notNull()->unique(),
            'profile_id' => $this->bigInteger()->unique()->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string()->notNull(),
            'password_reset_token' => $this->string()->unique(),
            'verification_token' => $this->string()->unique(),
            'email_verified_at' => $this->integer(),
            'mobile_verified_at' => $this->integer(),
            'last_login_at' => $this->integer(),
            'last_login_ip' => $this->string(45),
            'is_locked' => $this->boolean()->notNull()->defaultValue(false),
            'status' => $this->smallInteger()->notNull()->defaultValue(10), // 10 = active
            'is_deleted' => $this->tinyInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),

            'FOREIGN KEY ([[profile_id]]) REFERENCES {{%profiles}} ([[profile_id]])' .
                $this->buildFkClause('ON DELETE CASCADE', 'ON UPDATE CASCADE'),
        ], $this->tableOptions);

        $this->createIndex('idx-users-status', '{{%users}}', 'status');

        // 3. JWT Refresh Tokens (this is your "long-lived session")
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

        // Critical indexes for fast cleanup & lookup
        $this->createIndex('idx-jwt_refresh-expires', '{{%jwt_refresh_tokens}}', 'expires_at');
        $this->createIndex('idx-jwt_refresh-jti', '{{%jwt_refresh_tokens}}', 'jti');
        $this->createIndex('idx-jwt_refresh-user', '{{%jwt_refresh_tokens}}', 'user_id');

        // 4. Optional: Blacklisted JWTs (for immediate logout all devices)
        $this->createTable('{{%jwt_blacklist}}', [
            'id' => $this->bigPrimaryKey(),
            'jti' => $this->string(128)->notNull()->unique(),
            'expires_at' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $this->tableOptions);

        $this->createIndex('idx-jwt_blacklist-expires', '{{%jwt_blacklist}}', 'expires_at');

        // 5. Login attempts (rate limiting & brute force protection)
        $this->createTable('{{%login_attempts}}', [
            'attempt_id' => $this->bigPrimaryKey(),
            'user_id' => $this->bigInteger(),
            'username' => $this->string(64)->notNull(),
            'ip_address' => $this->string(45)->notNull(),
            'success' => $this->boolean()->defaultValue(false),
            'created_at' => $this->integer()->notNull(),
        ], $this->tableOptions);

        $this->createIndex('idx-login_attempts-ip', '{{%login_attempts}}', 'ip_address');
        $this->createIndex('idx-login_attempts-created', '{{%login_attempts}}', 'created_at');

        // === Create Admin User ===
        $time = time();
        $this->insert('{{%profiles}}', [
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email_address' => 'admin@example.com',
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        $profileId = (new \yii\db\Query())->from('{{%profiles}}')->select('profile_id')->orderBy(['profile_id' => SORT_DESC])->limit(1)->scalar();

        $this->insert('{{%users}}', [
            'username' => 'admin',
            'profile_id' => $profileId,
            'auth_key' => Yii::$app->security->generateRandomString(),
            'password_hash' => Yii::$app->security->generatePasswordHash('@dmiN123'),
            'email_verified_at' => $time,
            'status' => 10,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $this->createTable('{{%password_history}}', [
            'password_history_id' => $this->bigPrimaryKey(),
            'user_id' => $this->bigInteger()->notNull(),
            'old_password' => $this->string()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'FOREIGN KEY ([[user_id]]) REFERENCES {{%users}} ([[user_id]])' .
                $this->buildFkClause('ON DELETE CASCADE', 'ON UPDATE CASCADE'),
        ], $this->tableOptions);

        $this->createTable('{{%access_log}}', [
            'access_id' => $this->primaryKey(),
            'user_id' => $this->bigInteger(),
            'action' => $this->string(50)->notNull()->comment('e.g., login, logout, page_view, failed_login'),
            'description' => $this->text()->null(),
            'extra_data' => $this->text()->null(),
            'ip_address' => $this->string(45)->null()->comment('Supports IPv4 and IPv6'),
            'user_agent' => $this->text()->null(),
            'is_deleted' => $this->integer(2)->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'FOREIGN KEY ([[user_id]]) REFERENCES {{%users}} ([[user_id]])' .
                $this->buildFkClause('ON DELETE CASCADE', 'ON UPDATE CASCADE'),
        ], $this->tableOptions);
    }
    public function down()
    {
        $this->dropTable('{{%access_log}}');
        $this->dropTable('{{%password_history}}');
        $this->dropTable('{{%jwt_blacklist}}');
        $this->dropTable('{{%jwt_refresh_tokens}}');
        $this->dropTable('{{%login_attempts}}');
        $this->dropTable('{{%users}}');
        $this->dropTable('{{%profiles}}');
    }
}
