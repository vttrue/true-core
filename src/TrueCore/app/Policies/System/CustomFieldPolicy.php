<?php

namespace TrueCore\App\Policies\System;

use TrueCore\App\Policies\BasePolicy;
use TrueCore\App\Services\System\{CustomField\CustomField as CustomFieldService,User as UserService};

/**
 * Class CustomFieldPolicy
 *
 * @package TrueCore\App\Policies\System
 */
class CustomFieldPolicy extends BasePolicy
{
    /**
     * @param UserService $user
     * @param CustomFieldService|null $entity
     *
     * @return bool
     */
    public function read(UserService $user, ?CustomFieldService $entity)
    {
        return $this->checkPermission($user, $entity, 'read');
    }

    /**
     * @param UserService $user
     * @param CustomFieldService|null $entity
     *
     * @return bool
     */
    public function readOwn(UserService $user, ?CustomFieldService $entity)
    {
        return $this->checkPermission($user, $entity, 'readOwn');
    }

    /**
     * @param UserService $user
     * @param CustomFieldService|null $entity
     *
     * @return bool
     */
    public function write(UserService $user, ?CustomFieldService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param CustomFieldService|null $entity
     *
     * @return bool
     */
    public function writeOwn(UserService $user, ?CustomFieldService $entity)
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
     * @param CustomFieldService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function update(UserService $user, CustomFieldService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param CustomFieldService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function delete(UserService $user, CustomFieldService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param CustomFieldService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function restore(UserService $user, CustomFieldService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param CustomFieldService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function forceDelete(UserService $user, CustomFieldService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }
}
