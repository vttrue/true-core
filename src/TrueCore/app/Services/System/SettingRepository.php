<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 23.06.2020
 * Time: 22:45
 */

namespace TrueCore\App\Services\System;

use \TrueCore\App\Services\Repository;
use \TrueCore\App\Models\System\Setting as SettingModel;

/**
 * Class SettingRepository
 *
 * @method SettingModel getModel()
 *
 * @package \TrueCore\App\Services\System
 */
class SettingRepository extends Repository
{
    protected array $sortableFields = [
        'id',
        'name',
        'role_name',
        'phone',
        'email',
        'last_visit_at',
        'created_at',
        'updated_at',
        'status',
    ];

    protected array $searchableFields = [
        'name',
        'role_name',
        'phone',
        'email',
        'last_visit_at',
        'created_at',
        'updated_at',
    ];

    protected array $relationFields = [
        'role_name' => [
            'relation' => 'role',
            'fields'   => [
                'name',
            ],
        ],
    ];

    protected array $switchableFields = [
        'status',
    ];

    protected array $eagerRelations = [
        '*' => [
            'role',
            'role.entities',
            'owner'
        ],
        'role'  => [
            'role',
            'role.entities'
        ],
        'owner' => [
            'owner'
        ]
    ];

    /**
     * SettingRepository constructor.
     *
     * @param SettingModel $settingModel
     */
    public function __construct(SettingModel $settingModel)
    {
        parent::__construct($settingModel);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function normalizeData(array $data) : array
    {
        //
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
     * @return array
     */
    protected function getDataStructure(): array
    {
        // @TODO: should we better use getProcessors instead? Consider this regarding backwards compatibility | Deprecator @ 2020-06-24
        $value = $this->getModel()->value;

        if ($this->getModel()->json === true && is_string($value)) {
            $decodedJson = json_decode($value, true);

            if (is_array($decodedJson) === true) {
                $value = $decodedJson;
            }
        }

        return [
            'id'            => fn () : int    => (int)$this->getModel()->id,
            'group'         => fn () : string => $this->getModel()->group,
            'key'           => fn () : string => $this->getModel()->key,
            'value'         => static fn ()   => $value,
            'createdAt'     => fn() : ?string => (($this->getModel()->created_at !== null) ? $this->getModel()->created_at->format('Y-m-d H:i:s') : null),
            'updatedAt'     => fn() : ?string => (($this->getModel()->updated_at !== null) ? $this->getModel()->updated_at->format('Y-m-d H:i:s') : null)
        ];
    }
}
