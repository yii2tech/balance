<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

use yii\base\BaseObject;

/**
 * CallbackSerializer serializes data via custom PHP callback.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class CallbackSerializer extends BaseObject implements SerializerInterface
{
    /**
     * @var callable PHP callback, which should be used to serialize value.
     */
    public $serialize;
    /**
     * @var callable PHP callback, which should be used to unserialize value.
     */
    public $unserialize;


    /**
     * {@inheritdoc}
     */
    public function serialize($value)
    {
        return call_user_func($this->serialize, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($value)
    {
        return call_user_func($this->unserialize, $value);
    }
}