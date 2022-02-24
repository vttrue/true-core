<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 22.10.2019
 * Time: 16:49
 */

namespace TrueCore\App\Services\System;

use \TrueCore\App\Services\Factory;
use \TrueCore\App\Models\System\Role as RoleModel;

class RoleFactory extends Factory
{
    /**
     * @return Role
     * @throws \Exception
     */
    public function create()
    {
        return new Role(new RoleRepository(new RoleModel), $this, new RoleObserver);
    }
}