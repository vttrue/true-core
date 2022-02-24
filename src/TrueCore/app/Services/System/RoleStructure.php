<?php

namespace TrueCore\App\Services\System;

use \TrueCore\App\Services\Structure;

/**
 * Class RoleStructure
 *
 * @property int $id
 * @property string $name
 * @property array|null $owner
 * @property int|null $userCount
 * @property int|null $entityCount
 * @property array $permissions
 * @property string|null $createdAt
 * @property string|null $updatedAt
 *
 * @package TrueCore\App\Services\System
 */
class RoleStructure extends Structure
{
    /**
     * RoleStructure constructor.
     *
     * @param RoleRepository $repository
     */
    public function __construct(RoleRepository $repository)
    {
        parent::__construct($repository);
    }
}
