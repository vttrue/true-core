<?php

namespace TrueCore\App\Services\System\Entity;

use \TrueCore\App\Services\Repository;
use \TrueCore\App\Models\System\Entity as EntityModel;

/**
 * Class EntityRepository
 *
 * @method EntityModel getModel()
 *
 * @package \TrueCore\App\Services\System\Entity
 */
class EntityRepository extends Repository
{
    protected array $sortableFields = [
        'id',
        'name',
    ];

    protected array $searchableFields = [
        'name',
        'created_at',
        'updated_at',
    ];

    protected array $switchableFields = [
        'status',
    ];

    /**
     * EntityRepository constructor.
     *
     * @param EntityModel $entityModel
     */
    public function __construct(EntityModel $entityModel)
    {
        parent::__construct($entityModel);
    }

    /**
     * @param array $data
     *
     * @throws \Throwable
     */
    protected function processModification(array $data = []): void
    {
        //
    }

    /**
     * @return array|\Closure[]
     */
    protected function getDataStructure(): array
    {
        return [
            'id'         => fn(): int => (int) $this->getModel()->id,
            'name'       => fn(): ?string => $this->getModel()->name,
            'namespace'  => fn(): ?string => $this->getModel()->namespace,
            'controller' => fn(): ?string => $this->getModel()->controller,
            'policy'     => fn(): ?string => $this->getModel()->policy,
            'sortOrder'  => fn(): int => (int) $this->getModel()->sort_order,
            'status'     => fn(): bool => (($this->getModel()->status) ? true : false),
            'createdAt'  => fn(): ?string => (($this->getModel()->created_at) ? $this->getModel()->created_at->format('Y-m-d H:i:s') : null),
            'updatedAt'  => fn(): ?string => (($this->getModel()->updated_at) ? $this->getModel()->updated_at->format('Y-m-d H:i:s') : null),
        ];
    }
}
