<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 22.10.2019
 * Time: 17:45
 */

namespace TrueCore\App\Services\System;

use \TrueCore\App\Services\Repository;
use \TrueCore\App\Models\System\User as UserModel;

/**
 * Class UserRepository
 *
 * @method UserModel getModel()
 *
 * @package \TrueCore\App\Services\System
 */
class UserRepository extends Repository
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
     * UserRepository constructor.
     *
     * @param UserModel $userModel
     */
    public function __construct(UserModel $userModel)
    {
        parent::__construct($userModel);
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

        if (array_key_exists('role', $normalizedData) && is_array($normalizedData['role']) && array_key_exists('id', $normalizedData['role']) && is_numeric($normalizedData['role']['id']) && (int)$normalizedData['role']['id'] > 0) {
            $normalizedData['roleId'] = $normalizedData['role']['id'];
        }

        return $normalizedData;
    }

    /**
     * @param array $data
     *
     * @throws \Throwable
     */
    protected function processModification(array $data = []): void
    {
        if (array_key_exists('password', $data) && is_string($data['password']) && $data['password'] !== '') {
            $this->getModel()->password = $data['password'];
            $this->getModel()->saveOrFail();
        }
    }

    /**
     * @TODO: separate role permissions into a single method in order to avoid repeating same code | deprecator @ 2019-11-13
     *
     * @return array
     */
    protected function getDataStructure(): array
    {
        $owner = null;

        if ($this->getModel()->owner) {
            $owner = [
                'id'   => (int)$this->getModel()->owner->id,
                'name' => $this->getModel()->owner->name,
            ];
        }

        return [
            'id'            => fn(): int => (int)$this->getModel()->id,
            'name'          => fn(): ?string => $this->getModel()->name,
            'role'          => fn(): array => [
                'id'          => (int)$this->getModel()->role->id,
                'name'        => $this->getModel()->role->name,
                'permissions' => $this->getModel()->role->entities->map(function ($item) {

                    $permissions = [
                        'read'     => false,
                        'write'    => false,
                        'readOwn'  => false,
                        'writeOwn' => false,
                    ];

                    if (!is_array($item->pivot->permissions) || (is_string($item->pivot->permissions) && $item->pivot->permissions !== '')) {
                        $permissions = array_fill_keys(json_decode($item->pivot->permissions, true), true) + $permissions;
                    }

                    return [
                        'id'          => (int)$item->pivot->entity_id,
                        'alias'       => str_replace('TrueCore\\', '', str_replace(['App\Http\Controllers\Admin\\', 'Controller'], '', $item->controller)),
                        'policy'      => $item->policy,
                        'service'     => $item->namespace,
                        'permissions' => $permissions,
                        'status'      => (($item->status) ? true : false),
                    ];
                })->toArray(),
            ],
            'phone'         => fn(): ?string => $this->getModel()->phone,
            'email'         => fn(): ?string => $this->getModel()->email,
            'password'      => fn(): ?string => $this->getModel()->password,
            'owner'         => fn(): ?array => $owner,
            'rememberToken' => fn(): ?string => $this->getModel()->remember_token,
            'status'        => fn(): bool => (($this->getModel()->status) ? true : false),
            'isEditable'    => fn(): bool => (($this->getModel()->is_editable) ? true : false),
            'createdAt'     => fn(): ?string => (($this->getModel()->created_at) ? $this->getModel()->created_at->format('Y-m-d H:i:s') : null),
            'updatedAt'     => fn(): ?string => (($this->getModel()->updated_at) ? $this->getModel()->updated_at->format('Y-m-d H:i:s') : null),
            'lastVisitedAt' => fn(): ?string => (($this->getModel()->last_visit_at) ? $this->getModel()->last_visit_at->format('Y-m-d H:i:s') : null),
        ];
    }
}
