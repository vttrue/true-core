<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 20.09.2019
 * Time: 1:02
 */

namespace TrueCore\App\Services;

use \TrueCore\App\Services\Interfaces\Factory as FactoryInterface;

/**
 * Class Factory
 *
 * @package TrueCore\App\Services
 */
abstract class Factory implements FactoryInterface
{
    abstract public function create(); // FactoryInterface
}