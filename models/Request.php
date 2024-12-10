<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property int $amount
 * @property int $term
 * @property int $status
 */
class Request extends ActiveRecord
{
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_DECLINED = 2;

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%requests}}';
    }

    /**
     * Defines validation rules for the model.
     * @return array
     */
    public function rules(): array
    {
        return [
            [['user_id', 'amount', 'term'], 'required'],
            [['user_id', 'amount', 'term', 'status'], 'integer'],
            [['amount', 'term'], 'integer', 'min' => 0],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            //[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * The relationship “the request belongs to the user”.
     * @return \yii\db\ActiveQuery
     */
    public function getUser(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public static function getStatusCodes(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_DECLINED,
        ];
    }

    public static function getStatusList(): array
    {
        return [
            self::STATUS_PENDING => Yii::t('app', 'Pending'),
            self::STATUS_APPROVED => Yii::t('app', 'Approved'),
            self::STATUS_DECLINED => Yii::t('app', 'Rejected'),
        ];
    }
}
