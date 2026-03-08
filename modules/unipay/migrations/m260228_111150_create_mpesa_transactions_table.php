<?php

use helpers\Migration;

class m260228_111150_create_mpesa_transactions_table extends Migration
{
    public function up()
    {
        $this->createTable('{{%mpesa_transactions}}', [
            'id'                          => $this->bigPrimaryKey(),
            'user_id'                     => $this->bigInteger()->null()->comment('Link to the initiator or payer'),
            'type'                        => $this->string(10)->notNull()->comment('C2B, B2C, or B2B'),
            'mpesa_receipt'               => $this->string(50)->unique(),
            'amount'                      => $this->decimal(10, 2)->notNull(),
            'phone'                       => $this->string(20),
            'status'                      => $this->string(20)->defaultValue('PENDING'),
            'conversation_id'             => $this->string(100)->unique(),
            'originator_conversation_id'  => $this->string(100)->unique(),
            'created_at'                  => $this->integer()->notNull(),
            'updated_at'                  => $this->integer()->notNull(),

            'FOREIGN KEY ([[user_id]]) REFERENCES {{%users}} ([[user_id]])'
                . $this->buildFkClause('ON DELETE SET NULL', 'ON UPDATE CASCADE'),
        ], $this->tableOptions);

        $this->createIndex('idx-mpesa-type',    '{{%mpesa_transactions}}', 'type');
        $this->createIndex('idx-mpesa-status',  '{{%mpesa_transactions}}', 'status');
        $this->createIndex('idx-mpesa-receipt', '{{%mpesa_transactions}}', 'mpesa_receipt');
        $this->createIndex('idx-mpesa-phone',   '{{%mpesa_transactions}}', 'phone');
    }

    public function down()
    {
        $this->dropTable('{{%mpesa_transactions}}');
    }
}