<?php

namespace TrueCore\App\Services\System\CustomField;

use TrueCore\App\Services\Repository;
use TrueCore\App\Models\System\CustomField as CustomFieldModel;

/**
 * Class CustomFieldRepository
 *
 * @method CustomFieldModel getModel()
 *
 * @package App\Services\System\CustomField
 */
class CustomFieldRepository extends Repository
{
    protected array $eagerRelations = [
        '*' => [
            'owner'
        ],
        'owner' => [
            'owner'
        ]
    ];

    /**
     * CustomFieldRepository constructor.
     *
     * @param CustomFieldModel $model
     */
    public function __construct(CustomFieldModel $model)
    {
        parent::__construct($model);
    }

    protected function processModification(array $data = []): void
    {
        //
    }

    /**
     * @return array
     */
    protected function getDataStructure(): array
    {
        return [
            'id'        => function () {
                return (int)$this->getModel()->id;
            },
            'code'      => function () {
                return $this->getModel()->code;
            },
            'sortOrder' => function () {
                return (int)$this->getModel()->sort_order;
            },
            'status'    => function () {
                return (($this->getModel()->status) ? true : false);
            },
            'owner'     => function () {
                return (($this->getModel()->owner !== null) ? [
                    'id'   => (int)$this->getModel()->owner->id,
                    'name' => $this->getModel()->owner->name,
                ] : null);
            },
            'createdAt' => function () {
                return (($this->getModel()->created_at !== null) ? $this->getModel()->created_at->format('Y-m-d H:i:s') : null);
            },
            'updatedAt' => function () {
                return (($this->getModel()->updated_at !== null) ? $this->getModel()->updated_at->format('Y-m-d H:i:s') : null);
            },
        ];
    }
}
