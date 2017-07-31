<?php

namespace yii2tech\tests\unit\balance;

use yii\mongodb\Connection;
use yii\mongodb\Query;
use yii2tech\balance\ManagerMongoDb;

/**
 * @group mongodb
 */
class ManagerMongoDbTest extends TestCase
{
    /**
     * @var Connection MongoDB connection used for the test running.
     */
    protected $db;

    public function setUp()
    {
        $this->mockApplication([
            'components' => [
                'mongodb' => $this->getDb()
            ],
        ]);
    }

    protected function tearDown()
    {
        $db = $this->getDb();
        try {
            $db->getCollection('BalanceAccount', true)->drop();
            $db->getCollection('BalanceTransaction', true)->drop();
        } catch (\yii\mongodb\Exception $e) {
            // shutdown exception
        }
        $db->close();

        parent::tearDown();
    }

    /**
     * @return Connection test database connection
     */
    protected function getDb()
    {
        if ($this->db === null) {
            if (!extension_loaded('mongodb')) {
                $this->markTestSkipped('mongo PHP extension required.');
                return null;
            }
            if (!class_exists('yii\mongodb\Connection')) {
                $this->markTestSkipped('"yiisoft/yii2-mongodb" extension required.');
                return null;
            }

            $connectionConfig = $this->getParam('mongodb', [
                'dsn' => 'mongodb://travis:test@localhost:27017',
                'defaultDatabaseName' => 'yii2test',
                'options' => [],
            ]);

            $this->db = new Connection($connectionConfig);
            $this->db->open();
        }
        return $this->db;
    }

    /**
     * @return ManagerMongoDb test manager instance.
     */
    protected function createManager()
    {
        $manager = new ManagerMongoDb();
        $manager->accountCollection = 'BalanceAccount';
        $manager->transactionCollection = 'BalanceTransaction';
        return $manager;
    }

    /**
     * @return array last saved transaction data.
     */
    protected function getLastTransaction()
    {
        return (new Query())
            ->from('BalanceTransaction')
            ->orderBy(['_id' => SORT_DESC])
            ->limit(1)
            ->one();
    }

    // Tests :

    public function testIncrease()
    {
        $manager = $this->createManager();

        $manager->increase(1, 50);
        $transaction = $this->getLastTransaction();
        $this->assertEquals(50, $transaction['amount']);

        $manager->increase(1, 50, ['extra' => 'custom']);
        $transaction = $this->getLastTransaction();
        $this->assertContains('custom', $transaction['extra']);
    }

    /**
     * @depends testIncrease
     */
    public function testAutoCreateAccount()
    {
        $manager = $this->createManager();

        $manager->autoCreateAccount = true;
        $manager->increase(['userId' => 5], 10);
        $accounts = (new Query())->from('BalanceAccount')->all();
        $this->assertCount(1, $accounts);
        $this->assertEquals(5, $accounts[0]['userId']);

        $manager->autoCreateAccount = false;
        $this->expectException('yii\base\InvalidParamException');
        $manager->increase(['userId' => 10], 10);
    }

    /**
     * @depends testAutoCreateAccount
     */
    public function testIncreaseAccountBalance()
    {
        $manager = $this->createManager();
        $manager->autoCreateAccount = true;
        $manager->accountBalanceAttribute = 'balance';

        $amount = 50;
        $manager->increase(['userId' => 1], $amount);
        $account = (new Query())->from('BalanceAccount')->andWhere(['userId' => 1])->one();

        $this->assertEquals($amount, $account['balance']);
    }

    /**
     * @depends testIncrease
     */
    public function testRevert()
    {
        $manager = $this->createManager();

        $accountId = 1;
        $amount = 10;
        $transactionId = $manager->increase($accountId, $amount);
        $manager->revert($transactionId);

        $transaction = $this->getLastTransaction();
        $this->assertEquals($accountId, $transaction['accountId']);
        $this->assertEquals(-$amount, $transaction['amount']);
    }

    /**
     * @depends testIncrease
     */
    public function testCalculateBalance()
    {
        $manager = $this->createManager();

        $manager->increase(1, 50);
        $manager->increase(2, 50);
        $manager->decrease(1, 25);

        $this->assertEquals(25, $manager->calculateBalance(1));
    }
}