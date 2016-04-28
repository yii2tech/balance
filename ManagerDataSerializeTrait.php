<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\balance;

use yii\di\Instance;

/**
 * ManagerDataSerializeTrait provides ability to serialize extra attributes into the single field.
 * It may be useful using data storage with static data schema, like relational database.
 * This trait supposed to be used inside descendant of [[Manager]].
 *
 * @see Manager
 * @see SerializerInterface
 *
 * @property string|array|SerializerInterface $serializer serializer instance or its configuration.
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
     * @var string|array|SerializerInterface serializer instance or its configuration.
     * Following shortcuts are supported:
     *
     * - 'php' - use [[PhpSerializer]]
     * - 'json' - use [[JsonSerializer]]
     *
     * Using array configuration, you may omit 'class' parameter, in this case [[CallbackSerializer]] will be used.
     * For example:
     *
     * ```php
     * [
     *     'serialize' => function ($value) { return serialize($value); },
     *     'unserialize' => function ($value) { return unserialize($value); },
     * ]
     * ```
     */
    private $_serializer = 'json';


    /**
     * @return SerializerInterface serializer instance
     */
    public function getSerializer()
    {
        if (!is_object($this->_serializer)) {
            $this->_serializer = $this->createSerializer($this->_serializer);
        }
        return $this->_serializer;
    }

    /**
     * @param SerializerInterface|array|string $serializer serializer to be used
     */
    public function setSerializer($serializer)
    {
        $this->_serializer = $serializer;
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
            $safeAttributes[$this->dataAttribute] = $this->getSerializer()->serialize($dataAttributes);
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

        $dataAttributes = $this->getSerializer()->unserialize($attributes[$this->dataAttribute]);
        unset($attributes[$this->dataAttribute]);
        return array_merge($attributes, $dataAttributes);
    }

    /**
     * Creates serializer from given configuration.
     * @param string|array $config serializer configuration.
     * @return SerializerInterface serializer instance
     */
    protected function createSerializer($config)
    {
        if (is_string($config)) {
            switch ($config) {
                case 'php':
                    $config = [
                        'class' => PhpSerializer::className()
                    ];
                    break;
                case 'json':
                    $config = [
                        'class' => JsonSerializer::className()
                    ];
                    break;
            }
        } elseif (is_array($config)) {
            if (!isset($config['class'])) {
                $config['class'] = CallbackSerializer::className();
            }
        }
        return Instance::ensure($config, 'yii2tech\balance\SerializerInterface');
    }
}