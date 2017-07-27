<?php

namespace yii2tech\tests\unit\balance\data;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $userId
 * @property int $balance
 */
class BalanceAccount extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'BalanceAccount';
    }
}