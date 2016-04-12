<?php

namespace yii2tech\tests\unit\balance;

use yii2tech\tests\unit\balance\data\ManagerMock;

class ManagerTest extends TestCase
{
    public function testIncrease()
    {
        $manager = new ManagerMock();

        $manager->increase(1, 50);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals(50, $transaction['amount']);

        $manager->increase(1, 50, ['extra' => 'custom']);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals('custom', $transaction['extra']);
    }

    /**
     * @depends testIncrease
     */
    public function testDecrease()
    {
        $manager = new ManagerMock();

        $manager->decrease(1, 50);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals(-50, $transaction['amount']);
    }

    /**
     * @depends testIncrease
     */
    public function testTransfer()
    {
        $manager = new ManagerMock();

        $manager->transfer(1, 2, 10);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals(10, $transaction['amount']);

        $manager->transfer(1, 2, 10, ['extra' => 'custom']);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals('custom', $transaction['extra']);
    }

    /**
     * @depends testIncrease
     */
    public function testDateAttributeValue()
    {
        $manager = new ManagerMock();

        $now = time();
        $manager->increase(1, 10);
        $transaction = $manager->getLastTransaction();
        $this->assertTrue($transaction['date'] >= $now);

        $manager->dateAttributeValue = function() {
            return 'callback';
        };
        $manager->increase(1, 10);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals('callback', $transaction['date']);

        $manager->dateAttributeValue = new \DateTime();
        $manager->increase(1, 10);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals($manager->dateAttributeValue, $transaction['date']);
    }

    /**
     * @depends testIncrease
     */
    public function testAutoCreateAccount()
    {
        $manager = new ManagerMock();

        $manager->autoCreateAccount = true;
        $manager->increase(['userId' => 5], 10);
        $this->assertCount(1, $manager->accounts);

        $manager->autoCreateAccount = false;
        $this->setExpectedException('yii\base\InvalidParamException');
        $manager->increase(['userId' => 10], 10);
    }
}