<?php

namespace yii2tech\tests\unit\balance\data;

use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $userId
 * @property integer $balance
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