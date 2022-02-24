<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 22.10.2019
 * Time: 16:48
 */

namespace TrueCore\App\Services\System;

use \TrueCore\App\Services\Repository;
use \TrueCore\App\Models\System\Role as RoleModel;

/**
 * Class RoleRepository
 *
 * @method RoleModel getModel()
 *
 * @package \TrueCore\App\Services\System
 */
class RoleRepository extends Repository
{
    protected array $sortableFields = [
        'id',
        'name',
        'created_at',
        'updated_at'
    ];

    protected array $searchableFields = [
        'id',
        'name',
        'created_at',
        'updated_at'
    ];

    protected array $eagerRelations = [
        '*' => [
            'entities',
            'owner'
        ],
        'permissions'   => [
            'entities'
        ],
        'owner' => [
            'owner'
        ]
    ];

    protected array $eagerCountRelations = [
        '*' => [
            'users',
            'entities'
        ],
        'userCount' => [
            'users'
        ],
        'entityCount'   => [
            'entities'
        ]
    ];

    /**
     * RoleRepository constructor.
     *
     * @param RoleModel $roleModel
     */
    public function __construct(RoleModel $roleModel)
    {
        parent::__construct($roleModel);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function normalizeData(array $data) : array
    {
        $normalizedData = parent::normalizeData($data);

        if (array_key_exists('user', $normalizedData) && is_array($normalizedData['user']) && array_key_exists('id', $normalizedData['user']) && is_numeric($normalizedData['user']['id']) && (int)$normalizedData['user']['id'] > 0) {
            $normalizedData['userId'] = (int)$normalizedData['user']['id'];
        }

        return $normalizedData;
    }

    /**
     * @param array $data
     * @throws \Exception
     */
    protected function processModification(array $data = []) : void
    {
        $entityList = ((array_key_exists('permissions', $data) && is_array($data['permissions'])) ? $data['permissions'] : null);

        if(is_array($entityList)) {
            $this->savePermission($entityList);
        }
    }

    /**
     * @param array $entityList
     */
    private function savePermission(array $entityList)
    {
        $this->getModel()->entities()->detach();

        if (count($entityList) > 0) {

            $entityList = array_map(function($v) {

                $v['permissions'] = array_keys(array_filter($v['permissions'], function($v) {
                    return $v === true;
                }));

                return $v;

            }, array_filter($entityList, function($v) {
                return (
                    is_array($v) &&
                    array_key_exists('id', $v) &&
                    (is_string($v['id']) || is_int($v['id'])) &&
                    is_array($v['permissions']) &&
                    count(array_filter($v['permissions'], function($p, $pk) {
                        return (in_array($pk, ['read', 'write', 'readOwn', 'writeOwn']) && is_bool($p));
                    }, ARRAY_FILTER_USE_BOTH)) > 0
                );
            }));

            foreach ($entityList as $entity) {

                $this->getModel()->entities()->attach($entity['id'], [
                    'permissions' => json_encode($entity['permissions'])
                ]);

            }

        }
    }

    /**
     * @return array
     */
    protected function getDataStructure(): array
    {
        $owner = null;

        if($this->getModel()->owner) {
            $owner = [
                'id'    => (int)$this->getModel()->owner->id,
                'name'  => $this->getModel()->owner->name
            ];
        }

        return [
            'id'            => (int)$this->getModel()->id,
            'name'          => $this->getModel()->name,
            'userCount'     => ((is_numeric($this->getModel()->users_count)) ? $this->getModel()->users_count : $this->getModel()->users()->count()),
            'entityCount'   => ((is_numeric($this->getModel()->entities_count)) ? $this->getModel()->entities_count : $this->getModel()->entities()->count()),
            'permissions'   => $this->getModel()->entities->map(function ($item) {

                $permissions = [
                    'read'      => false,
                    'write'     => false,
                    'readOwn'   => false,
                    'writeOwn'  => false
                ];

                if(!is_array($item->pivot->permissions) || (is_string($item->pivot->permissions) && $item->pivot->permissions !== '')) {

                    $permissions = array_fill_keys(json_decode($item->pivot->permissions, true), true) + $permissions;

                }

                return [
                    'id'            => (int)$item->pivot->entity_id,
                    'alias'         => str_replace(['App\Http\Controllers\Admin\\', 'Controller'], '', $item->controller),
                    'permissions'   => $permissions,
                    'status'        => (($item->status) ? true : false)
                ];
            })->toArray(),
            'owner'         => $owner,
            'createdAt'     => (($this->getModel()->created_at) ? $this->getModel()->created_at->format('Y-m-d H:i:s') : null),
            'updatedAt'     => (($this->getModel()->updated_at) ? $this->getModel()->updated_at->format('Y-m-d H:i:s') : null)
        ];
    }
}
