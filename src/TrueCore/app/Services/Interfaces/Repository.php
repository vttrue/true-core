<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 20.09.2019
 * Time: 0:44
 */

namespace TrueCore\App\Services\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface Repository
{
    /**
     * @return mixed
     */
    public function getModel();

    /**
     * @param array $data
     * @param callable|null $successCallback
     * @param callable|null $failCallback
     *
     * @return Repository
     */
    public function add(array $data, ?callable $successCallback = null, ?callable $failCallback = null); // Repository

    /**
     * @param array $data
     * @param callable|null $successCallback
     * @param callable|null $failCallback
     *
     * @return bool
     */
    public function update(array $data, ?callable $successCallback = null, ?callable $failCallback = null) : bool;

    /**
     * @param string $field
     * @return bool
     */
    public function switch(string $field) : bool;

    /**
     * @param array|null $relations
     *
     * @return mixed
     */
    public function copy(?array $relations = null);

    /**
     * @return bool
     */
    public function touch() : bool;

    /**
     * @param bool $soft
     * @return bool
     */
    public function delete(bool $soft = false) : bool;

    /**
     * @return bool
     */
    public function isSaving() : bool;

    /**
     * @param array $options
     * @param array $columns
     * @return Repository[]|array
     */
    public function getAll(array $options = [], array $columns = ['*']);

    /**
     * @param array $options
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPaginator(array $options = [], int $perPage = 15);

    /**
     * @param array $options
     * @param int $offset
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllDynamicPaginator(array $options = [], $offset = 0, $perPage = 15);

    /**
     * @param array $conditions
     * @param array $options
     *
     * @return Repository|null
     */
    public function getOne(array $conditions = [], array $options = []);

    /**
     * @param array $conditions
     * @param array $options
     *
     * @return Repository[]|null
     */
    public function getRandom(array $conditions = [], array $options = []);

    /**
     * @param array $conditions
     * @param array $options
     *
     * @return int
     */
    public function count(array $conditions = [], array $options = []) : int;

    /**
     * @param Repository[] $items
     * @param array|null $fields
     *
     * @return array
     */
    public function mapList(array $items, ?array $fields = null) : array;

    /**
     * @param array|null $fields
     *
     * @return array
     */
    public function mapDetail(?array $fields = null) : array;
}
