<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%requests}}`.
 */
class m241208_191843_create_requests_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%requests}}', [
            'id' => $this->primaryKey()->unsigned()->comment('ID заявки'),
            'user_id' => $this->integer()->notNull()->unsigned()->comment('ID пользователя'),
            'amount' => $this->integer()->notNull()->unsigned()->comment('Сумма кредита'),
            'term' => $this->integer()->notNull()->unsigned()->comment('Срок кредита'),
            'status' => $this->integer()->notNull()->unsigned()->comment('Статус заявки')
        ]);

        // Create index for status
        $this->createIndex(
            'idx-requests-status',  // Index name
            '{{%requests}}', // Table name
            'status' // Column
        );
        // Create composite index for status и user_id to speed up selects
        $this->createIndex(
            'idx-requests-user_id-status', // Index name
            '{{%requests}}', // Table name
            ['user_id', 'status']  // Columns in descending order of cardinality
        );

        // Create foreign key to users.id
        $this->addForeignKey(
            'fk-requests-user_id', // Unique name of the foreign key
            'requests', // Table where the foreign key is created
            'user_id', // The field in the requests table that will be referenced
            'users', // Table to which the foreign key will be referenced
            'id', // Field in the users table to which the foreign key is referenced
            'CASCADE', // Action when deleting a record in users (CASCADE deletes linked records)
            'CASCADE' // Action when updating a record in users
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key to users.id
        $this->dropForeignKey(
            'fk-requests-user_id',
            'requests'
        );

        // Drop composite index
        $this->dropIndex(
            'idx-requests-user_id-status',
            '{{%requests}}'
        );

        // Drop index for status
        $this->dropIndex(
            'idx-requests-status',
            '{{%requests}}'
        );

        // Drop table requests
        $this->dropTable('{{%requests}}');
    }
}
