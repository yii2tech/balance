<?php

namespace yii2tech\tests\unit\balance\data;

/**
 * Manager mock class for the unit test
 */
class ManagerMock extends \yii2tech\balance\Manager
{
    /**
     * @var array[] list of accounts
     */
    public $accounts = [];
    /**
     * @var array[] list of performed transactions
     */
    public $transactions = [];


    /**
     * @return array last transaction data.
     */
    public function getLastTransaction()
    {
        return end($this->transactions);
    }

    /**
     * @inheritdoc
     */
    protected function writeTransaction($attributes)
    {
        $this->transactions[] = $attributes;
        return count($this->transactions);
    }

    /**
     * @inheritdoc
     */
    protected function findAccountId($attributes)
    {
        $id = serialize($attributes);
        if (isset($this->accounts[$id])) {
            return $id;
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function createAccount($attributes)
    {
        $id = serialize($attributes);
        $this->accounts[$id] = $id;
        return $id;
    }
}