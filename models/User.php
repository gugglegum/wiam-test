<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Model User
 *
 * @property int $id;
 * @property string $username;
 * @property string $email;
 * @property string $password_hash;
 * @property int $created_at;
 * @property int $updated_at;
 */
class User extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%users}}';
    }

    public function rules(): array
    {
        return [
            [['username', 'email', 'password_hash'], 'required'],
            [['username'], 'string', 'max' => 64],
            [['email'], 'string', 'max' => 128],
            [['password_hash'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['username', 'email'], 'unique'],
        ];
    }
}
