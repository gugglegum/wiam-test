<?php

use yii\db\Migration;

/**
 * Создает таблицу users
 */
class m241208_191049_create_users_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%users}}', [
            'id' => $this->primaryKey()->unsigned()->comment('ID пользователя'),
            'username' => $this->string(64)->notNull()->unique()->comment('Имя пользователя'),
            'email' => $this->string(128)->notNull()->unique()->comment('Email'),
            'password_hash' => $this->string(255)->notNull()->comment('Хэш-пароля bcrypt'),
            'created_at' => $this->bigInteger()->notNull()->unsigned()->comment('Время создания'),
            'updated_at' => $this->bigInteger()->unsigned()->comment('Время изменения'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%users}}');
    }
}
