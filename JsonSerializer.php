<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

use yii\base\BaseObject;
use yii\helpers\Json;

/**
 * JsonSerializer serializes data in JSON format.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class JsonSerializer extends BaseObject implements SerializerInterface
{
    /**
     * @var int the encoding options. For more details please refer to
     * <http://www.php.net/manual/en/function.json-encode.php>.
     * Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
     */
    public $options = 320;


    /**
     * {@inheritdoc}
     */
    public function serialize($value)
    {
        return Json::encode($value, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($value)
    {
        return Json::decode($value);
    }
}