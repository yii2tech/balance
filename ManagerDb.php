<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

use yii\db\Connection;
use yii\di\Instance;

/**
 * ManagerDb
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
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }
}