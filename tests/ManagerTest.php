<?php

namespace yii2tech\tests\unit\balance;

use yii2tech\tests\unit\balance\data\ManagerMock;

class ManagerTest extends TestCase
{
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
     * @depends testTransfer
     */
    public function testDateAttributeValue()
    {
        $manager = new ManagerMock();

        $now = time();
        $manager->transfer(1, 2, 10);
        $transaction = $manager->getLastTransaction();
        $this->assertTrue($transaction['date'] >= $now);

        $manager->dateAttributeValue = function() {
            return 'callback';
        };
        $manager->transfer(1, 2, 10);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals('callback', $transaction['date']);

        $manager->dateAttributeValue = new \DateTime();
        $manager->transfer(1, 2, 10);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals($manager->dateAttributeValue, $transaction['date']);
    }

    /**
     * @depends testTransfer
     */
    public function testAutoCreateAccount()
    {
        $manager = new ManagerMock();

        $manager->autoCreateAccount = true;
        $manager->transfer(['userId' => 5], 2, 10);
        $this->assertCount(1, $manager->accounts);

        $manager->autoCreateAccount = false;
        $this->setExpectedException('yii\base\InvalidParamException');
        $manager->transfer(['userId' => 10], 2, 10);
    }
}