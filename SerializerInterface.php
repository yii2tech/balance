<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

/**
 * SerializerInterface defines serializer interface.
 *
 * @see ManagerDataSerializeTrait
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
interface SerializerInterface
{
    /**
     * Serializes given value.
     * @param mixed $value value to be serialized
     * @return string serialized value.
     */
    public function serialize($value);

    /**
     * Restores value from its serialized representations
     * @param string $value serialized string.
     * @return mixed restored value
     */
    public function unserialize($value);
}