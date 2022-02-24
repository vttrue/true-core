<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 14.11.2019
 * Time: 0:30
 */

namespace TrueCore\App\Services;

use \TrueCore\App\Services\Interfaces\Repository;

/**
 * Class Structure
 *
 * @property Repository $repository
 *
 * @package TrueCore\App\Services
 */
abstract class Structure implements \ArrayAccess
{
    protected Repository $repository;
    protected array $_fields = [];
    protected int $_fieldIteratorPosition = 0;
    protected array $_fieldKeys   = [];

    /**
     * Structure constructor.
     *
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get(string $name)
    {
        if(!array_key_exists($name, $this->getFields())) {

            $getterName = 'get' . ucfirst($name) . 'Field';

            if(method_exists($this, $getterName)) {
                return $this->{$getterName}();
            }

            throw new \Exception('Requested field "' . $name . '" was not set in ' . basename(str_replace('\\', '/', static::class)) . ' nor was a getter (' . $getterName . '()) declared.');
        }

        return $this->getFields()[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $setterName = 'set' . ucfirst($name) . 'Field';

        if(method_exists($this, $setterName)) {
            $this->{$setterName}($value);
        } else {
            $this->_fields[$name] = $value;
        }
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return ($this->getField($offset) !== null);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getField($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->_fields[$offset] = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->_fields[$offset]);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * @return $this
     */
    public function getList()
    {
        return $this;
    }

    /**
     * @param array|null $fields
     *
     * @return $this
     */
    public function getDetail(?array $fields = null)
    {
        $this->setFields($this->repository->mapDetail($fields));

        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return $this->getFields();
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getField(string $name)
    {
        return ((array_key_exists($name, $this->getFields())) ? $this->getFields()[$name] : null);
    }

    /**
     * @return array
     */
    private function getFields()
    {
        return $this->_fields;
    }

    /**
     * @param array $fields
     */
    private function setFields(array $fields)
    {
        foreach ($fields AS $field => $value) {
            $this->_fields[$field] = (($value instanceof \Closure) ? $value() : $value);
        }
    }
}
