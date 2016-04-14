<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

use yii\base\InvalidConfigException;
use yii\helpers\Json;

/**
 * ManagerDataSerializeTrait provides ability to serialize extra attributes into the single field.
 * It may be useful using data storage with static data schema, like relational database.
 * This trait supposed to be used inside descendant of [[Manager]].
 *
 * @see Manager
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
trait ManagerDataSerializeTrait
{
    /**
     * @var string name of the transaction entity attribute, which should be used to store serialized data.
     */
    public $dataAttribute = 'data';
    /**
     * @var string|callable serialize method. Following methods are supported:
     *
     * - 'php' - use native PHP `serialize()` and `unserialize()` methods
     * - 'json' - use JSON format for serialization.
     *
     * This can be a PHP callback, which will be used for both(!) serialize and unserialize.
     * Such callback should determine which operation needed based on incoming argument.s
     * For example:
     *
     * ```php
     * function ($value) {
     *     if (is_string($value)) {
     *         return unserialize($value);
     *     }
     *     return serialize($value);
     * }
     * ```
     */
    public $serializeMethod = 'json';


    /**
     * Serializes given data.
     * @param array $data data to be serialized
     * @return string serialized data.
     * @throws InvalidConfigException on invalid [[serializeMethod]]
     */
    protected function serialize($data)
    {
        if (is_scalar($this->serializeMethod)) {
            switch ($this->serializeMethod) {
                case 'php':
                    return serialize($data);
                case 'json':
                    return Json::encode($data);
                default:
                    throw new InvalidConfigException("Unrecognized serialize method '{$this->serializeMethod}'");
            }
        }
        return call_user_func($this->serializeMethod, $data);
    }

    /**
     * Unserializes given data.
     * @param string $data serialized data
     * @return array unserialized data.
     * @throws InvalidConfigException on invalid [[serializeMethod]]
     */
    protected function unserialize($data)
    {
        if (is_scalar($this->serializeMethod)) {
            switch ($this->serializeMethod) {
                case 'php':
                    return unserialize($data);
                case 'json':
                    return Json::decode($data);
                default:
                    throw new InvalidConfigException("Unrecognized serialize method '{$this->serializeMethod}'");
            }
        }
        return call_user_func($this->serializeMethod, $data);
    }

    /**
     * Processes attributes to be saved in persistent storage, serializing the ones not allowed for direct storage.
     * @param array $attributes raw attributes to be processed.
     * @param array $allowedAttributes list of attribute names, which are allowed to be saved in persistent stage.
     * @return array actual attributes.
     */
    protected function serializeAttributes($attributes, $allowedAttributes)
    {
        if ($this->dataAttribute === null) {
            return $attributes;
        }

        $safeAttributes = [];
        $dataAttributes = [];
        foreach ($attributes as $name => $value) {
            if (in_array($name, $allowedAttributes, true)) {
                $safeAttributes[$name] = $value;
            } else {
                $dataAttributes[$name] = $value;
            }
        }
        if (!empty($dataAttributes)) {
            $safeAttributes[$this->dataAttribute] = $this->serialize($dataAttributes);
        }

        return $safeAttributes;
    }

    /**
     * Processes the raw entity attributes from the persistent storage, converting serialized data into
     * actual attributes list.
     * @param array $attributes raw attribute values from persistent storage.
     * @return array actual attribute values
     */
    protected function unserializeAttributes($attributes)
    {
        if ($this->dataAttribute === null) {
            return $attributes;
        }

        if (empty($attributes[$this->dataAttribute])) {
            unset($attributes[$this->dataAttribute]);
            return $attributes;
        }

        $dataAttributes = $this->unserialize($attributes[$this->dataAttribute]);
        unset($attributes[$this->dataAttribute]);
        return array_merge($attributes, $dataAttributes);
    }
}