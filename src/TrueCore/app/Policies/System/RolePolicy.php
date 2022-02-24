<?php

namespace TrueCore\App\Policies\System;

use TrueCore\App\Policies\BasePolicy;
use TrueCore\App\Services\System\{
    Role as RoleService,
    User as UserService
};

/**
 * Class RolePolicy
 *
 * @package TrueCore\App\Policies\System
 */
class RolePolicy extends BasePolicy
{
    /**
     * @param UserService $user
     * @param RoleService|null $entity
     *
     * @return bool
     */
    public function read(UserService $user, ?RoleService $entity)
    {
        return $this->checkPermission($user, $entity, 'read');
    }

    /**
     * @param UserService $user
     * @param RoleService|null $entity
     *
     * @return bool
     */
    public function readOwn(UserService $user, ?RoleService $entity)
    {
        return $this->checkPermission($user, $entity, 'readOwn');
    }

    /**
     * @param UserService $user
     * @param RoleService|null $entity
     *
     * @return bool
     */
    public function write(UserService $user, ?RoleService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param RoleService|null $entity
     *
     * @return bool
     */
    public function writeOwn(UserService $user, ?RoleService $entity)
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
     * @param RoleService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function update(UserService $user, RoleService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param RoleService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function delete(UserService $user, RoleService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param RoleService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function restore(UserService $user, RoleService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param RoleService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function forceDelete(UserService $user, RoleService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }
}
