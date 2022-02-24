<?php

namespace TrueCore\App\Models\Traits;

/**
 * Trait HasCompositePrimaryKey
 *
 * @property array|null $compositePrimaryKey
 *
 * @package TrueCore\App\Models\Traits
 */
trait HasCompositePrimaryKey
{
    /**
     * @return array|null
     */
    public function getCompositeKeyName()
    {
        return ((property_exists($this, 'compositePrimaryKey') === true && is_array($this->compositePrimaryKey) === true) ? $this->compositePrimaryKey : null);
    }

    /**
     * @return array|null
     */
    public function getCompositeKey()
    {
        if ( is_array($this->getCompositeKeyName()) === true && count($this->getCompositeKeyName()) ) {

            $attributes = [];

            foreach ($this->getCompositeKeyName() as $key) {
                $attributes[$key] = $this->getAttribute($key);
            }

            return $attributes;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    public function getKey()
    {
        return null;
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    protected function setKeysForSaveQuery($query)
    {
        if (is_array($this->getCompositeKeyName()) === true && count($this->getCompositeKeyName()) > 0) {

            foreach ($this->getCompositeKeyName() as $key) {

                if ($this->{$key} !== null) {
                    $query->where($key, '=', $this->{$key});
                } else {
                    throw new Exception(__METHOD__ . 'Missing part of the primary key: ' . $key);
                }

            }
        }

        return $query;
    }

    /**
     * @param null|string $keyName
     *
     * @return mixed
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        return null;
    }

    /**
     * Execute a query for a single record by ID.
     *
     * @param  array  $ids Array of keys, like [column => value].
     * @param  array  $columns
     * @return mixed|static
     */
    public static function find($ids, $columns = ['*'])
    {
        $me = new self;
        $query = $me->newQuery();

        foreach ($me->getCompositeKeyName() as $key) {
            $query->where($key, '=', $ids[$key]);
        }

        return $query->first($columns);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param mixed $ids
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function findOrFail($ids, $columns = ['*'])
    {
        $result = self::find($ids, $columns);

        if ($result !== null) {
            return $result;
        }

        throw (new ModelNotFoundException)->setModel(
            __CLASS__, $ids
        );
    }

    /**
     * Reload the current model instance with fresh attributes from the database.
     *
     * @return $this
     */
    public function refresh()
    {
        if ($this->exists === false) {
            return $this;
        }

        $this->setRawAttributes(
            static::findOrFail($this->getCompositeKey())->attributes
        );

        $this->load(collect($this->relations)->except('pivot')->keys()->toArray());

        return $this;
    }
}
