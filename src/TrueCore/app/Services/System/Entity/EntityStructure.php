<?php

namespace TrueCore\App\Services\System\Entity;

use \TrueCore\App\Services\Structure;

/**
 * Class EntityStructure
 *
 * @property int $id
 * @property string $name
 * @property string|null $namespace
 * @property string|null $controller
 * @property string|null $policy
 * @property bool $status
 * @property int $sortOrder
 * @property string|null $createdAt
 * @property string|null $updatedAt
 *
 * @package TrueCore\App\Services\System\Entity
 */
class EntityStructure extends Structure
{
    /**
     * EntityStructure constructor.
     *
     * @param EntityRepository $repository
     */
    public function __construct(EntityRepository $repository)
    {
        parent::__construct($repository);
    }
}
