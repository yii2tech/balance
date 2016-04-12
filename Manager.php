<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

use yii\base\Component;
use yii\base\InvalidParamException;
use yii\helpers\VarDumper;

/**
 * Manager is a base class for the balance managers.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class Manager extends Component implements ManagerInterface
{
    /**
     * @var boolean whether to automatically create requested account, if it does not yet exist.
     */
    public $autoCreateAccount = true;
    /**
     * @var string name of the transaction entity attribute, which should store amount.
     */
    public $amountAttribute = 'amount';
    /**
     * @var string name of the transaction entity attribute, which should store associated account ID.
     */
    public $accountAttribute = 'accountId';
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


    public function transfer($from, $to, $amount, $data = [])
    {
        $fromId = $this->fetchAccountId($from);
        $toId = $this->fetchAccountId($to);

        $data[$this->dateAttribute] = $this->getDateAttributeValue();

        $fromData = $data;
        $fromData[$this->amountAttribute] = -$amount;
        $fromData[$this->accountAttribute] = $fromId;

        $toData = $data;
        $toData[$this->amountAttribute] = $amount;
        $toData[$this->accountAttribute] = $toId;

        $this->writeTransaction($fromData);
        $this->writeTransaction($toData);
    }

    public function revert($transactionId)
    {
        ;
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
                    throw new InvalidParamException('Unable to find account matching filter: ' . VarDumper::export($idOrFilter));
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
    abstract protected function writeTransaction($attributes);

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
}