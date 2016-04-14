<?php

namespace yii2tech\tests\unit\balance;

use yii2tech\tests\unit\balance\data\ManagerDataSerialize;

class ManagerDataSerializeTraitTest extends TestCase
{
    /**
     * @return array
     */
    public function dataProviderSerializeMethod()
    {
        return [
            ['json'],
            ['php'],
            [
                function ($value) {
                    if (is_string($value)) {
                        return unserialize($value);
                    }
                    return serialize($value);
                }
            ],
        ];
    }

    /**
     * @dataProvider dataProviderSerializeMethod
     *
     * @param string|callable $serializeMethod
     */
    public function testSerialize($serializeMethod)
    {
        $manager = new ManagerDataSerialize();
        $manager->serializeMethod = $serializeMethod;

        $manager->increase(1, 50, ['extra' => 'custom']);
        $transaction = $manager->getLastTransaction();
        $this->assertEquals(50, $transaction['amount']);
        $this->assertContains('custom', $transaction['data']);
    }

    /**
     * @depends testSerialize
     * @dataProvider dataProviderSerializeMethod
     *
     * @param string|callable $serializeMethod
     */
    public function testUnserialize($serializeMethod)
    {
        $manager = new ManagerDataSerialize();
        $manager->serializeMethod = $serializeMethod;
        $manager->extraAccountLinkAttribute = 'extraAccountId';

        $fromId = 10;
        $toId = 20;
        $transactionIds = $manager->transfer($fromId, $toId, 10);
        $manager->revert($transactionIds[0]);

        $this->assertCount(4, $manager->transactions);
    }
}