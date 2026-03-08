<?php

use helpers\Migration;

class m260228_111232_create_mpesa_responses_table extends Migration
{
    public function up()
    {
        $this->createTable('{{%mpesa_responses}}', [
            'id'             => $this->bigPrimaryKey(),
            'transaction_id' => $this->bigInteger()->notNull(),
            'raw_payload'    => $this->json()->comment('Full JSON response from Safaricom'),
            'result_code'    => $this->integer(),
            'result_desc'    => $this->string(255),
            'created_at'     => $this->integer()->notNull(),
            'updated_at'     => $this->integer()->notNull(),

            'FOREIGN KEY ([[transaction_id]]) REFERENCES {{%mpesa_transactions}} ([[id]])'
                . $this->buildFkClause('ON DELETE CASCADE', 'ON UPDATE CASCADE'),
        ], $this->tableOptions);

        $this->createIndex('idx-mpesa-res-tid',  '{{%mpesa_responses}}', 'transaction_id');
        $this->createIndex('idx-mpesa-res-code', '{{%mpesa_responses}}', 'result_code');
    }

    public function down()
    {
        $this->dropTable('{{%mpesa_responses}}');
    }
}