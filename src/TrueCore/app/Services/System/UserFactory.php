<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 22.10.2019
 * Time: 17:45
 */

namespace TrueCore\App\Services\System;

use \TrueCore\App\Services\Factory;
use \TrueCore\App\Models\System\User as UserModel;

/**
 * Class UserFactory
 *
 * @package TrueCore\App\Services\System
 */
class UserFactory extends Factory
{
    /**
     * @return User
     * @throws \Exception
     */
    public function create()
    {
        return new User(new UserRepository(new UserModel), $this, new UserObserver);
    }
}