<?php

namespace yii2tech\tests\unit\balance;

use Yii;
use yii\db\Query;
use yii2tech\balance\ManagerDb;

class ManagerDbTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->setupTestDbData();
    }

    /**
     * Setup tables for test ActiveRecord
     */
    protected function setupTestDbData()
    {
        $db = Yii::$app->getDb();

        // Structure :

        $table = 'BalanceAccount';
        $columns = [
            'id' => 'pk',
            'userId' => 'integer',
            'amount' => 'string',
        ];
        $db->createCommand()->createTable($table, $columns)->execute();

        $table = 'BalanceTransaction';
        $columns = [
            'id' => 'pk',
            'date' => 'integer',
            'accountId' => 'integer',
            'amount' => 'integer',
            'data' => 'text',
        ];
        $db->createCommand()->createTable($table, $columns)->execute();
    }

    protected function getLastTransaction()
    {
        return (new Query())
            ->from('BalanceTransaction')
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->one();
    }

    // Tests :

    public function testIncrease()
    {
        $manager = new ManagerDb();

        $manager->increase(1, 50);
        $transaction = $this->getLastTransaction();
        $this->assertEquals(50, $transaction['amount']);

        /*$manager->increase(1, 50, ['extra' => 'custom']);
        $transaction = $this->getLastTransaction();
        $this->assertEquals('custom', $transaction['extra']);*/
    }
}