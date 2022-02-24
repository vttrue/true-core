<?php

namespace TrueCore\App\Policies\System;

use TrueCore\App\Policies\BasePolicy;
use TrueCore\App\Services\System\User as UserService;

/**
 * Class UserPolicy
 *
 * @package TrueCore\App\Policies\System
 */
class UserPolicy extends BasePolicy
{
    /**
     * @param UserService $user
     * @param UserService|null $entity
     *
     * @return bool
     */
    public function read(UserService $user, ?UserService $entity)
    {
        return $this->checkPermission($user, $entity, 'read');
    }

    /**
     * @param UserService $user
     * @param UserService|null $entity
     *
     * @return bool
     */
    public function readOwn(UserService $user, ?UserService $entity)
    {
        return $this->checkPermission($user, $entity, 'readOwn');
    }

    /**
     * @param UserService $user
     * @param UserService|null $entity
     *
     * @return bool
     */
    public function write(UserService $user, ?UserService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param UserService|null $entity
     *
     * @return bool
     */
    public function writeOwn(UserService $user, ?UserService $entity)
    {
        return $this->checkPermission($user, $entity, 'writeOwn');
    }

    /**
     * @param UserService $user
     *
     * @return bool
     * @throws \Exception
     */
    public function create(UserService $user)
    {
        return $this->checkPermission($user, null, 'write');
    }

    /**
     * @param UserService $user
     * @param UserService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function update(UserService $user, UserService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param UserService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function delete(UserService $user, UserService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param UserService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function restore(UserService $user, UserService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param UserService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function forceDelete(UserService $user, UserService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }
}
