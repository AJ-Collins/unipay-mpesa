<?php

use helpers\Migration;

/**
 * Handles the creation of table `{{%mpesa_reconciliation}}`.
 */
class m260306_150123_create_mpesa_reconciliation_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%mpesa_reconciliation}}', [
            'id'             => $this->bigPrimaryKey(),
            'transaction_id' => $this->bigInteger()->notNull(),
            'mpesa_receipt'  => $this->string(50)->unique()->null(),
            'amount'         => $this->decimal(10, 2)->notNull(),
            'type'           => $this->string(10)->notNull(),
            'phone'          => $this->string(20)->null(),
            'reconciled'     => $this->boolean()->defaultValue(false),
            'reconciled_at'  => $this->integer()->null(),
            'notes'          => $this->string(500)->null(),
            'created_at'     => $this->integer()->notNull(),
            'FOREIGN KEY ([[transaction_id]]) REFERENCES {{%mpesa_transactions}} ([[id]])'
                . $this->buildFkClause('ON DELETE CASCADE', 'ON UPDATE CASCADE'),
        ], $this->tableOptions);

        $this->createIndex('idx-recon-tx-id',      '{{%mpesa_reconciliation}}', 'transaction_id');
        $this->createIndex('idx-recon-reconciled',  '{{%mpesa_reconciliation}}', 'reconciled');
        $this->createIndex('idx-recon-receipt',     '{{%mpesa_reconciliation}}', 'mpesa_receipt');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%mpesa_reconciliation}}');
    }
}
