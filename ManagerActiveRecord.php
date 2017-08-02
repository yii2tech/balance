<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

use yii\base\Model;
use yii\db\ActiveRecordInterface;
use yii\db\BaseActiveRecord;

/**
 * ManagerActiveRecord is a balance manager, which uses ActiveRecord classes for data storage.
 * This manager allows usage of any storage, which have ActiveRecord interface implemented, such as
 * relational DB, MongoDB, Redis etc. However, it may lack efficiency comparing to the dedicated managers
 * like [[ManagerDb]] or [[ManagerMongoDb]].
 *
 * Configuration example:
 *
 * ```php
 * return [
 *     'components' => [
 *         'balanceManager' => [
 *             'class' => 'yii2tech\balance\ManagerActiveRecord',
 *             'accountClass' => 'app\models\BalanceAccount',
 *             'transactionClass' => 'app\models\BalanceTransaction',
 *             'accountBalanceAttribute' => 'balance',
 *             'extraAccountLinkAttribute' => 'extraAccountId',
 *             'dataAttribute' => 'data',
 *         ],
 *     ],
 *     ...
 * ];
 * ```
 *
 * This manager will attempt to save value from transaction data in the attribute, which name matches data key.
 * If such attribute does not exist data will be saved in [[dataAttribute]] column in serialized state.
 *
 * > Note: watch for the keys you use in transaction data: make sure they do not conflict with attributes, which are
 *   reserved for other purposes, like primary keys.
 *
 * @see Manager
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class ManagerActiveRecord extends ManagerDbTransaction
{
    use ManagerDataSerializeTrait;

    /**
     * @var string name of the ActiveRecord class, which should store account records.
     */
    public $accountClass;
    /**
     * @var string name of the ActiveRecord class, which should store transaction records.
     */
    public $transactionClass;


    /**
     * {@inheritdoc}
     */
    protected function findAccountId($attributes)
    {
        /* @var $class ActiveRecordInterface */
        $class = $this->accountClass;
        $model = $class::find()->andWhere($attributes)->one();
        if (!is_object($model)) {
            return null;
        }
        /* @var $model ActiveRecordInterface */
        return $model->getPrimaryKey(false);
    }

    /**
     * {@inheritdoc}
     */
    protected function findTransaction($id)
    {
        /* @var $class ActiveRecordInterface */
        $class = $this->transactionClass;
        $model = $class::findOne($id);
        if (!is_object($model)) {
            return null;
        }
        /* @var $model ActiveRecordInterface|Model */
        return $this->unserializeAttributes($model->getAttributes());
    }

    /**
     * {@inheritdoc}
     */
    protected function createAccount($attributes)
    {
        /* @var $class ActiveRecordInterface */
        $class = $this->accountClass;
        /* @var $model ActiveRecordInterface|Model */
        $model = new $class();
        $model->setAttributes($attributes, false);
        $model->save(false);
        return $model->getPrimaryKey(false);
    }

    /**
     * {@inheritdoc}
     */
    protected function createTransaction($attributes)
    {
        /* @var $class ActiveRecordInterface */
        $class = $this->transactionClass;
        /* @var $model ActiveRecordInterface|Model */
        $model = new $class();
        $model->setAttributes($this->serializeAttributes($attributes, $model->attributes()), false);
        $model->save(false);
        return $model->getPrimaryKey(false);
    }

    /**
     * {@inheritdoc}
     */
    protected function incrementAccountBalance($accountId, $amount)
    {
        /* @var $class ActiveRecordInterface|BaseActiveRecord */
        $class = $this->accountClass;

        $primaryKeys = $class::primaryKey();
        $primaryKey = array_shift($primaryKeys);

        $class::updateAllCounters([$this->accountBalanceAttribute => $amount], [$primaryKey => $accountId]);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateBalance($account)
    {
        $accountId = $this->fetchAccountId($account);

        /* @var $class ActiveRecordInterface|BaseActiveRecord */
        $class = $this->transactionClass;

        return $class::find()
            ->andWhere([$this->accountLinkAttribute => $accountId])
            ->sum($this->amountAttribute);
    }

    /**
     * {@inheritdoc}
     */
    protected function createDbTransaction()
    {
        /* @var $class ActiveRecordInterface|BaseActiveRecord */
        $class = $this->transactionClass;
        $db = $class::getDb();

        if ($db->hasMethod('getTransaction') && $db->getTransaction() !== null) {
            return null;
        }

        if ($db->hasMethod('beginTransaction')) {
            return $db->beginTransaction();
        }

        return null;
    }
}