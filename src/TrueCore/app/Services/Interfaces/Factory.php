<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 20.09.2019
 * Time: 0:46
 */

namespace TrueCore\App\Services\Interfaces;

/**
 * Interface Factory
 *
 * @package TrueCore\App\Services\Interfaces
 */
interface Factory
{
    /**
     * @return mixed
     */
    public function create(); // Service
}