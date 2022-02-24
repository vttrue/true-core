<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 20.09.2019
 * Time: 0:38
 */

namespace TrueCore\App\Services\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use TrueCore\App\Services\Interfaces\Repository as RepositoryInterface;
use TrueCore\App\Services\Structure;

/**
 * Interface Service
 *
 * @package TrueCore\App\Services\Interfaces
 */
interface Service
{
    /**
     * @return Repository
     */
    public function getRepository();

    /**
     * @param Observer $observer
     * @param array $data
     * @return Service
     */
    public function addEventObserver(Observer $observer, array $data = []);

    /**
     * @param array $data
     * @return Service
     */
    public static function add(array $data);

    /**
     * @param array $data
     * @return bool
     */
    public function edit(array $data) : bool;

    /**
     * @param array|null $relations
     *
     * @return Service
     */
    public function copy(?array $relations = null) : Service;

    /**
     * @param bool $soft
     * @return bool
     */
    public function delete(bool $soft = false) : bool;

    /**
     * @param array $options
     * @param array $columns
     * @return Service[]
     */
    public static function getAll(array $options = [], array $columns = ['*']);

    /**
     * @param array $options
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public static function getAllPaginator(array $options = [], int $perPage = 15);

    /**
     * @param array $options
     * @param int $offset
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public static function getAllDynamicPaginator(array $options = [], $offset = 0, $perPage = 15);

    /**
     * @param array $conditions
     * @return Service|null
     */
    public static function getOne(array $conditions = []);

    /**
     * @param array $conditions
     * @return int
     */
    public static function count(array $conditions = []) : int;

    /**
     * @param array $items
     * @param array|null $fields
     *
     * @return Structure[]
     */
    public static function mapList(array $items, ?array $fields = null) : array;

    /**
     * @param array|null $fields
     *
     * @return Structure
     */
    public function mapDetail(?array $fields = null) : Structure;

    /**
     * @return bool
     */
    public function isPushJob(): bool;
}
