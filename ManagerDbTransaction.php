<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

/**
 * ManagerDbTransaction allows performing balance operations as a single Database transaction.
 *
 * @see Manager
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class ManagerDbTransaction extends Manager
{
    /**
     * @var object|\yii\db\Transaction current transaction instance.
     */
    private $dbTransaction;


    /**
     * @inheritdoc
     */
    public function increase($account, $amount, $data = [])
    {
        $this->beginDbTransaction();
        try {
            $result = parent::increase($account, $amount, $data);
            $this->commitDbTransaction();
            return $result;
        } catch (\Exception $e) {
            $this->rollBackDbTransaction();
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function transfer($from, $to, $amount, $data = [])
    {
        $this->beginDbTransaction();
        try {
            $result = parent::transfer($from, $to, $amount, $data);
            $this->commitDbTransaction();
            return $result;
        } catch (\Exception $e) {
            $this->rollBackDbTransaction();
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function revert($transactionId, $data = [])
    {
        $this->beginDbTransaction();
        try {
            $result = parent::revert($transactionId, $data);
            $this->commitDbTransaction();
            return $result;
        } catch (\Exception $e) {
            $this->rollBackDbTransaction();
            throw $e;
        }
    }

    /**
     * Begins transaction.
     */
    protected function beginDbTransaction()
    {
        $this->dbTransaction = $this->createDbTransaction();
    }

    /**
     * Commits current transaction.
     */
    protected function commitDbTransaction()
    {
        if ($this->dbTransaction !== null) {
            $this->dbTransaction->commit();
        }
        $this->dbTransaction = null;
    }

    /**
     * Rolls back current transaction.
     */
    protected function rollBackDbTransaction()
    {
        if ($this->dbTransaction !== null) {
            $this->dbTransaction->rollBack();
        }
        $this->dbTransaction = null;
    }

    /**
     * Creates transaction instance, actually beginning transaction.
     * If transactions are not supported, `null` will be returned.
     * @return object|\yii\db\Transaction|null transaction instance, `null` if transaction is not supported.
     */
    abstract protected function createDbTransaction();
}