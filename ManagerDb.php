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
 *             'accountTable' => '{{%BalanceAccount}}',
 *             'transactionTable' => '{{%BalanceTransaction}}',
 *             'accountBalanceAttribute' => 'balance',
 *             'extraAccountLinkAttribute' => 'extraAccountId',
 *             'dataAttribute' => 'data',
 *         ],
 *     ],
 *     ...
 * ];
 * ```
 *
 * Database migration example:
 *
 * ```php
 * $this->createTable('BalanceAccount', [
 *     'id' => $this->primaryKey(),
 *     'balance' => $this->integer()->notNull()->defaultValue(0),
 *     // ...
 * ]);
 *
 * $this->createTable('BalanceTransaction', [
 *     'id' => $this->primaryKey(),
 *     'date' => $this->integer()->notNull(),
 *     'accountId' => $this->integer()->notNull(),
 *     'extraAccountId' => $this->integer()->notNull(),
 *     'amount' => $this->integer()->notNull()->defaultValue(0),
 *     'data' => $this->text(),
 *     // ...
 * ]);
 * ```
 *
 * This manager will attempt to save value from transaction data in the table column, which name matches data key.
 * If such column does not exist data will be saved in [[dataAttribute]] column in serialized state.
 *
 * > Note: watch for the keys you use in transaction data: make sure they do not conflict with columns, which are
 *   reserved for other purposes, like primary keys.
 *
 * @see Manager
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class ManagerDb extends ManagerDbTransaction
{
    use ManagerDataSerializeTrait;

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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    protected function findAccountId($attributes)
    {
        $id = (new Query())
            ->select([$this->getAccountIdAttribute()])
            ->from($this->accountTable)
            ->andWhere($attributes)
            ->scalar($this->db);

        if ($id === false) {
            return null;
        }
        return $id;
    }

    /**
     * {@inheritdoc}
     */
    protected function findTransaction($id)
    {
        $idAttribute = $this->getTransactionIdAttribute();

        $row = (new Query())
            ->from($this->transactionTable)
            ->andWhere([$idAttribute => $id])
            ->one($this->db);

        if ($row === false) {
            return null;
        }
        return $this->unserializeAttributes($row);
    }

    /**
     * {@inheritdoc}
     */
    protected function createAccount($attributes)
    {
        $primaryKeys = $this->db->getSchema()->insert($this->accountTable, $attributes);
        if (count($primaryKeys) > 1) {
            return implode(',', $primaryKeys);
        }
        return array_shift($primaryKeys);
    }

    /**
     * {@inheritdoc}
     */
    protected function createTransaction($attributes)
    {
        $allowedAttributes = [];
        foreach ($this->db->getTableSchema($this->transactionTable)->columns as $column) {
            if ($column->isPrimaryKey && $column->autoIncrement) {
                continue;
            }
            $allowedAttributes[] = $column->name;
        }
        $attributes = $this->serializeAttributes($attributes, $allowedAttributes);
        $primaryKeys = $this->db->getSchema()->insert($this->transactionTable, $attributes);
        if (count($primaryKeys) > 1) {
            return implode(',', $primaryKeys);
        }
        return array_shift($primaryKeys);
    }

    /**
     * {@inheritdoc}
     */
    protected function incrementAccountBalance($accountId, $amount)
    {
        $value = new Expression("[[{$this->accountBalanceAttribute}]]+:amount", ['amount' => $amount]);
        $this->db->createCommand()
            ->update($this->accountTable, [$this->accountBalanceAttribute => $value], [$this->getAccountIdAttribute() => $accountId])
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function calculateBalance($account)
    {
        $accountId = $this->fetchAccountId($account);

        return (new Query())
            ->from($this->transactionTable)
            ->andWhere([$this->accountLinkAttribute => $accountId])
            ->sum($this->amountAttribute, $this->db);
    }

    /**
     * {@inheritdoc}
     */
    protected function createDbTransaction()
    {
        if ($this->db->getTransaction() !== null) {
            return null;
        }
        return $this->db->beginTransaction();
    }
}