<?php

namespace TrueCore\App\Policies\System;

use TrueCore\App\Policies\BasePolicy;
use TrueCore\App\Services\System\{
    Setting as SettingService,
    User as UserService
};

/**
 * Class SettingsPolicy
 *
 * @package TrueCore\App\Policies\System
 */
class SettingsPolicy extends BasePolicy
{
    /**
     * @param UserService $user
     * @param SettingService|null $entity
     *
     * @return bool
     */
    public function read(UserService $user, ?SettingService $entity)
    {
        return $this->checkPermission($user, $entity, 'read');
    }

    /**
     * @param UserService $user
     * @param SettingService|null $entity
     *
     * @return bool
     */
    public function readOwn(UserService $user, ?SettingService $entity)
    {
        return $this->checkPermission($user, $entity, 'readOwn');
    }

    /**
     * @param UserService $user
     * @param SettingService|null $entity
     *
     * @return bool
     */
    public function write(UserService $user, ?SettingService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param SettingService|null $entity
     *
     * @return bool
     */
    public function writeOwn(UserService $user, ?SettingService $entity)
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
     * @param SettingService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function update(UserService $user, SettingService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param SettingService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function delete(UserService $user, SettingService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param SettingService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function restore(UserService $user, SettingService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }

    /**
     * @param UserService $user
     * @param SettingService $entity
     *
     * @return bool
     * @throws \Exception
     */
    public function forceDelete(UserService $user, SettingService $entity)
    {
        return $this->checkPermission($user, $entity, 'write');
    }
}
