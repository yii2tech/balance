<?php

namespace yii2tech\tests\unit\balance\data;

use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $date
 * @property integer $accountId
 * @property integer $amount
 * @property string $data
 */
class BalanceTransaction extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'BalanceTransaction';
    }
}