<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\helpers\VarDumper;

/**
 * Manager is a base class for the balance managers.
 *
 * @see ManagerInterface
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class Manager extends Component implements ManagerInterface
{
    /**
     * @event TransactionEvent an event raised before creating new transaction. You may adjust
     * [[TransactionEvent::transactionData]] changing actual data to be saved.
     */
    const EVENT_BEFORE_CREATE_TRANSACTION = 'beforeCreateTransaction';
    /**
     * @event TransactionEvent an event raised after new transaction has been created. You may use
     * [[TransactionEvent::transactionId]] to get new transaction ID.
     */
    const EVENT_AFTER_CREATE_TRANSACTION = 'afterCreateTransaction';

    /**
     * @var bool whether to automatically create requested account, if it does not yet exist.
     */
    public $autoCreateAccount = true;
    /**
     * @var string name of the transaction entity attribute, which should store amount.
     */
    public $amountAttribute = 'amount';
    /**
     * @var string name of the transaction entity attribute, which should be used to link transaction entity with
     * account entity (store associated account ID).
     */
    public $accountLinkAttribute = 'accountId';
    /**
     * @var string name of the transaction entity attribute, which should store additional affected account ID.
     * This attribute will be filled only at `transfer()` method execution and will store ID of the account transferred
     * from or to, depending on the context.
     * If not set, no information about the extra account context will be saved.
     *
     * Note: absence of this field will affect logic of some methods like [[revert()]].
     */
    public $extraAccountLinkAttribute;
    /**
     * @var string name of the account entity attribute, which should store current balance value.
     */
    public $accountBalanceAttribute;
    /**
     * @var string name of the transaction entity attribute, which should store date.
     */
    public $dateAttribute = 'date';
    /**
     * @var mixed|callable value which should be used for new transaction date composition.
     * This can be plain value, object like [[\yii\db\Expression]] or a PHP callback, which returns it.
     * If not set PHP `time()` function will be used.
     */
    public $dateAttributeValue;


    /**
     * {@inheritdoc}
     */
    public function increase($account, $amount, $data = [])
    {
        $accountId = $this->fetchAccountId($account);

        if (!isset($data[$this->dateAttribute])) {
            $data[$this->dateAttribute] = $this->getDateAttributeValue();
        }
        $data[$this->amountAttribute] = $amount;
        $data[$this->accountLinkAttribute] = $accountId;

        $data = $this->beforeCreateTransaction($accountId, $data);

        if ($this->accountBalanceAttribute !== null) {
            $this->incrementAccountBalance($accountId, $data[$this->amountAttribute]);
        }
        $transactionId = $this->createTransaction($data);

        $this->afterCreateTransaction($transactionId, $accountId, $data);

        return $transactionId;
    }

    /**
     * {@inheritdoc}
     */
    public function decrease($account, $amount, $data = [])
    {
        return $this->increase($account, -$amount, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function transfer($from, $to, $amount, $data = [])
    {
        $fromId = $this->fetchAccountId($from);
        $toId = $this->fetchAccountId($to);

        $data[$this->dateAttribute] = $this->getDateAttributeValue();
        $fromData = $data;
        $toData = $data;

        if ($this->extraAccountLinkAttribute !== null) {
            $fromData[$this->extraAccountLinkAttribute] = $toId;
            $toData[$this->extraAccountLinkAttribute] = $fromId;
        }

        return [
            $this->decrease($fromId, $amount, $fromData),
            $this->increase($toId, $amount, $toData)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function revert($transactionId, $data = [])
    {
        $transaction = $this->findTransaction($transactionId);
        if (empty($transaction)) {
            throw new InvalidArgumentException("Unable to find transaction '{$transactionId}'");
        }

        $amount = $transaction[$this->amountAttribute];

        if ($this->extraAccountLinkAttribute !== null && isset($transaction[$this->extraAccountLinkAttribute])) {
            $fromId = $transaction[$this->accountLinkAttribute];
            $toId = $transaction[$this->extraAccountLinkAttribute];
            return $this->transfer($fromId, $toId, $amount, $data);
        } else {
            $accountId = $transaction[$this->accountLinkAttribute];
            return $this->decrease($accountId, $amount, $data);
        }
    }

    /**
     * @param mixed $idOrFilter account ID or filter condition.
     * @return mixed account ID.
     */
    protected function fetchAccountId($idOrFilter)
    {
        if (is_array($idOrFilter)) {
            $accountId = $this->findAccountId($idOrFilter);
            if ($accountId === null) {
                if ($this->autoCreateAccount) {
                    $accountId = $this->createAccount($idOrFilter);
                } else {
                    throw new InvalidArgumentException('Unable to find account matching filter: ' . VarDumper::export($idOrFilter));
                }
            }
        } else {
            $accountId = $idOrFilter;
        }

        return $accountId;
    }

    /**
     * Finds account ID matching given filter attributes.
     * @param array $attributes filter attributes.
     * @return mixed|null account ID, `null` - if not found.
     */
    abstract protected function findAccountId($attributes);

    /**
     * Finds transaction data by ID.
     * @param mixed $id transaction ID.
     * @return array|null transaction data, `null` - if not found.
     */
    abstract protected function findTransaction($id);

    /**
     * Creates new account with given attributes.
     * @param array $attributes account attributes in format: attribute => value
     * @return mixed created account ID.
     */
    abstract protected function createAccount($attributes);

    /**
     * Writes transaction data into persistent storage.
     * @param array $attributes attributes associated with transaction in format: attribute => value
     * @return mixed new transaction ID.
     */
    abstract protected function createTransaction($attributes);

    /**
     * Increases current account balance value.
     * @param mixed $accountId account ID.
     * @param int|float $amount amount to be added to the current balance.
     */
    abstract protected function incrementAccountBalance($accountId, $amount);

    /**
     * Returns actual now date value for the transaction.
     * @return mixed date attribute value.
     */
    protected function getDateAttributeValue()
    {
        if ($this->dateAttributeValue === null) {
            return time();
        }
        if (is_callable($this->dateAttributeValue)) {
            return call_user_func($this->dateAttributeValue);
        }
        return $this->dateAttributeValue;
    }

    // Events :

    /**
     * This method is invoked before creating transaction.
     * @param mixed $accountId account ID.
     * @param array $data transaction data.
     * @return array adjusted transaction data.
     */
    protected function beforeCreateTransaction($accountId, $data)
    {
        $event = new TransactionEvent([
            'accountId' => $accountId,
            'transactionData' => $data
        ]);
        $this->trigger(self::EVENT_BEFORE_CREATE_TRANSACTION, $event);
        return $event->transactionData;
    }

    /**
     * This method is invoked after transaction has been created.
     * @param mixed $transactionId transaction ID.
     * @param mixed $accountId account ID.
     * @param array $data transaction data.
     * @return array adjusted transaction data.
     */
    protected function afterCreateTransaction($transactionId, $accountId, $data)
    {
        $event = new TransactionEvent([
            'transactionId' => $transactionId,
            'accountId' => $accountId,
            'transactionData' => $data
        ]);
        $this->trigger(self::EVENT_AFTER_CREATE_TRANSACTION, $event);
    }
}