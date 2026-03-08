<?php

use helpers\Migration;

/**
 * Handles the creation of table `{{%mpesa_failures}}`.
 */
class m260306_150012_create_mpesa_failures_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%mpesa_failures}}', [
            'id'             => $this->bigPrimaryKey(),
            'transaction_id' => $this->bigInteger()->null()->comment('Nullable — failure may predate a record'),
            'type'           => $this->string(10)->notNull(),
            'result_code'    => $this->integer()->notNull(),
            'result_desc'    => $this->string(255)->null(),
            'raw_payload'    => $this->json()->null(),
            'created_at'     => $this->integer()->notNull(),
        ], $this->tableOptions);

        $this->createIndex('idx-fail-type',        '{{%mpesa_failures}}', 'type');
        $this->createIndex('idx-fail-result-code', '{{%mpesa_failures}}', 'result_code');
        $this->createIndex('idx-fail-tx-id',       '{{%mpesa_failures}}', 'transaction_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%mpesa_failures}}');
    }
}
