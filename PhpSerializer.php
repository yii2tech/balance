<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

use yii\base\BaseObject;

/**
 * PhpSerializer uses native PHP `serialize()` and `unserialize()` functions for the serialization.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class PhpSerializer extends BaseObject implements SerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serialize($value)
    {
        return serialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($value)
    {
        return unserialize($value);
    }
}