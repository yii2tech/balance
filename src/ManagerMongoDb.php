<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

use yii\di\Instance;
use yii\mongodb\Connection;
use yii\mongodb\Query;

/**
 * ManagerMongoDb is a balance manager, which uses MongoDB as data storage.
 *
 * This storage requires [yiisoft/yii2-mongodb](https://github.com/yiisoft/yii2-mongodb) extension installed.
 * This can be done via composer:
 *
 * ```
 * composer require --prefer-dist yiisoft/yii2-mongodb
 * ```
 *
 * Configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'balanceManager' => [
 *             'class' => 'yii2tech\balance\ManagerMongoDb',
 *             'accountCollection' => 'BalanceAccount',
 *             'transactionCollection' => 'BalanceTransaction',
 *             'accountBalanceAttribute' => 'balance',
 *             'extraAccountLinkAttribute' => 'extraAccountId',
 *         ],
 *     ],
 *     ...
 * ];
 * ```
 *
 * Since MongoDB is schema-less this manager allows to save any transaction data as plain record attribute, which will
 * be searchable. However MongoDB does not support transactions, thus usage of this manager may be risk prone.
 *
 * @see Manager
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class ManagerMongoDb extends Manager
{
    /**
     * @var Connection|array|string the MongoDB connection object or the application component ID of the MongoDB connection.
     * After the ManagerMongoDb object is created, if you want to change this property, you should only assign it
     * with a MongoDB connection object.
     */
    public $db = 'mongodb';
    /**
     * @var string|array name of the MongoDB collection, which should store account records.
     */
    public $accountCollection = 'BalanceAccount';
    /**
     * @var string|array name of the MongoDB collection, which should store transaction records.
     */
    public $transactionCollection = 'BalanceTransaction';


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }

    /**
     * {@inheritdoc}
     */
    protected function findAccountId($attributes)
    {
        $row = (new Query())
            ->select(['_id'])
            ->from($this->accountCollection)
            ->andWhere($attributes)
            ->one($this->db);

        if ($row === false) {
            return null;
        }
        return $row['_id'];
    }

    /**
     * {@inheritdoc}
     */
    protected function findTransaction($id)
    {
        $row = (new Query())
            ->from($this->transactionCollection)
            ->andWhere(['_id' => $id])
            ->one($this->db);

        if ($row === false) {
            return null;
        }
        return $row;
    }

    /**
     * {@inheritdoc}
     */
    protected function createAccount($attributes)
    {
        return $this->db->getCollection($this->accountCollection)->insert($attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function createTransaction($attributes)
    {
        return $this->db->getCollection($this->transactionCollection)->insert($attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function incrementAccountBalance($accountId, $amount)
    {
        return $this->db->getCollection($this->accountCollection)->update(['_id' => $accountId], ['$inc' => [$this->accountBalanceAttribute => $amount]]);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateBalance($account)
    {
        $accountId = $this->fetchAccountId($account);

        return (new Query())
            ->from($this->transactionCollection)
            ->andWhere([$this->accountLinkAttribute => $accountId])
            ->sum($this->amountAttribute, $this->db);
    }
}