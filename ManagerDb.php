<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

use yii\db\Connection;
use yii\db\Expression;
use yii\db\Query;
use yii\di\Instance;

/**
 * ManagerDb is a balance manager, which uses relational database as data storage.
 *
 * Configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'balanceManager' => [
 *             'class' => 'yii2tech\balance\ManagerDb',
 *         ],
 *     ],
 *     ...
 * ];
 * ```
 *
 * @see Manager
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class ManagerDb extends Manager
{
    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
     * After the ManagerDb object is created, if you want to change this property, you should only assign it
     * with a DB connection object.
     */
    public $db = 'db';
    /**
     * @var string name of the database table, which should store account records.
     */
    public $accountTable = '{{%BalanceAccount}}';
    /**
     * @var string name of the database table, which should store transaction records.
     */
    public $transactionTable = '{{%BalanceTransaction}}';

    /**
     * @var string name of the account ID attribute at [[accountTable]]
     */
    private $_accountIdAttribute;
    /**
     * @var string name of the transaction ID attribute at [[transactionTable]]
     */
    private $_transactionIdAttribute;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }

    /**
     * @return string
     */
    public function getAccountIdAttribute()
    {
        if ($this->_accountIdAttribute === null) {
            $primaryKey = $this->db->getTableSchema($this->accountTable)->primaryKey;
            $this->_accountIdAttribute = array_shift($primaryKey);
        }
        return $this->_accountIdAttribute;
    }

    /**
     * @param string $accountIdAttribute
     */
    public function setAccountIdAttribute($accountIdAttribute)
    {
        $this->_accountIdAttribute = $accountIdAttribute;
    }

    /**
     * @return string
     */
    public function getTransactionIdAttribute()
    {
        if ($this->_transactionIdAttribute === null) {
            $primaryKey = $this->db->getTableSchema($this->transactionTable)->primaryKey;
            $this->_transactionIdAttribute = array_shift($primaryKey);
        }
        return $this->_transactionIdAttribute;
    }

    /**
     * @param string $transactionIdAttribute
     */
    public function setTransactionIdAttribute($transactionIdAttribute)
    {
        $this->_transactionIdAttribute = $transactionIdAttribute;
    }

    /**
     * @inheritdoc
     */
    protected function findAccountId($attributes)
    {
        $id = (new Query())
            ->select([$this->getAccountIdAttribute()])
            ->from($this->accountTable)
            ->scalar($this->db);

        if ($id === false) {
            return null;
        }
        return $id;
    }

    /**
     * @inheritdoc
     */
    protected function findTransaction($id)
    {
        $idAttribute = $this->getTransactionIdAttribute();

        $row = (new Query())
            ->select([$idAttribute])
            ->from($this->accountTable)
            ->andWhere([$idAttribute => $id])
            ->one($this->db);

        if ($row === false) {
            return null;
        }
        return $row;
    }

    /**
     * @inheritdoc
     */
    protected function createAccount($attributes)
    {
        return $this->db->getSchema()->insert($this->accountTable, $attributes);
    }

    /**
     * @inheritdoc
     */
    protected function writeTransaction($attributes)
    {
        return $this->db->getSchema()->insert($this->transactionTable, $attributes);
    }

    /**
     * @inheritdoc
     */
    protected function incrementAccountBalance($accountId, $amount)
    {
        $value = new Expression("[[{$this->accountBalanceAttribute}]] + :amount", ['amount' => $amount]);
        $this->db->createCommand()
            ->update($this->accountTable, [$this->accountBalanceAttribute => $value], [$this->getAccountIdAttribute() => $accountId])
            ->execute();
    }

    /**
     * @inheritdoc
     */
    public function calculateBalance($account)
    {
        $accountId = $this->findAccountId($account);

        return (new Query())
            ->from($this->transactionTable)
            ->andWhere([$this->accountLinkAttribute => $accountId])
            ->sum($this->amountAttribute, $this->db);
    }
}