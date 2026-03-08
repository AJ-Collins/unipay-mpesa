<?php

use helpers\Migration;

/**
 * Handles the creation of table `{{%mpesa_webhook_logs}}`.
 */
class m260306_145855_create_mpesa_webhook_logs_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%mpesa_webhook_logs}}', [
            'id'              => $this->bigPrimaryKey(),
            'endpoint'        => $this->string(100)->notNull()->comment('Callback endpoint name'),
            'ip_address'      => $this->string(45)->null()->comment('Caller IP address'),
            'raw_payload'     => $this->json()->comment('Full raw body from Safaricom'),
            'safaricom_ip_ok' => $this->boolean()->defaultValue(false)->comment('IP whitelisted'),
            'processed'       => $this->boolean()->defaultValue(false),
            'error_message'   => $this->string(500)->null(),
            'created_at'      => $this->integer()->notNull(),
        ], $this->tableOptions);

        $this->createIndex('idx-whl-endpoint',  '{{%mpesa_webhook_logs}}', 'endpoint');
        $this->createIndex('idx-whl-processed', '{{%mpesa_webhook_logs}}', 'processed');
        $this->createIndex('idx-whl-ip',        '{{%mpesa_webhook_logs}}', 'ip_address');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%mpesa_webhook_logs}}');
    }
}
