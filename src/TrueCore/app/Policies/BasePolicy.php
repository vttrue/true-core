<?php

namespace TrueCore\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use TrueCore\App\Services\Interfaces\Service as ServiceInterface;

/**
 * Class BasePolicy
 *
 * @package TrueCore\App\Policies
 */
abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * @param ServiceInterface $user
     * @param ServiceInterface|null $entity
     * @param string $type
     *
     * @return bool
     */
    protected function checkPermission(ServiceInterface $user, ?ServiceInterface $entity, string $type): bool
    {
        if (in_array($type, ['read', 'write', 'readOwn', 'writeOwn']) === false) {
            return false;
        }

        $userData = $user->mapDetail(['id', 'role']);
        $userRole = $userData->role;

        if (is_array($userRole) && is_array($userRole['permissions'])) {

            $searchWhat     = (($entity !== null) ? get_class($entity) : static::class);
            $searchWhere    = array_column($userRole['permissions'], (($entity !== null) ? 'service' : 'policy'));

            $neededPermissionIndex = array_search($searchWhat, $searchWhere);

            if ($neededPermissionIndex !== false) {

                if (array_key_exists('permissions', $userRole['permissions'][$neededPermissionIndex]) && is_array($userRole['permissions'][$neededPermissionIndex]['permissions']) && count($userRole['permissions'][$neededPermissionIndex]['permissions']) > 0) {

                    if ($entity !== null) {

                        if (in_array($type, ['readOwn', 'writeOwn'], true) === true) {

                            $owner = $entity->mapDetail(['owner']);
                            $owner = ((is_array($owner)) ? $owner : null);

                            return ($userRole['permissions'][$neededPermissionIndex]['permissions'][$type] === true && (is_array($owner) && $owner['id'] === $userData->id));
                        }

                    }

                    return ($userRole['permissions'][$neededPermissionIndex]['permissions'][$type] === true);
                }
            }
        }

        return false;
    }
}
