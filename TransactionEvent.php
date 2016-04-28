<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

use yii\base\Event;

/**
 * TransactionEvent represents the event parameter used for an balance transaction related event.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class TransactionEvent extends Event
{
    /**
     * @var mixed transaction related account ID.
     */
    public $accountId;
    /**
     * @var array transaction data.
     */
    public $transactionData;
    /**
     * @var mixed transaction ID.
     */
    public $transactionId;
}