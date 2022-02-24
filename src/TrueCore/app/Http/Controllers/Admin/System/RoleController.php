<?php

namespace TrueCore\App\Http\Controllers\Admin\System;

use TrueCore\App\Http\Controllers\Admin\Base\Controller;
use TrueCore\App\Models\System\{
    Entity as mEntity
};
use TrueCore\App\Services\System\Role as RoleService;
use TrueCore\App\Http\Resources\Admin\System\{
    RoleForm,
    RoleList
};
use TrueCore\App\Http\Requests\Admin\System\{
    StoreRole,
    UpdateRole
};

/**
 * Class RoleController
 *
 * @package TrueCore\App\Http\Controllers\Admin\System
 */
class RoleController extends Controller
{
    protected ?string $listKey = 'roleList';
    protected ?string $itemKey = 'role';

    /**
     * RoleController constructor.
     *
     * @param RoleService $service
     */
    public function __construct(RoleService $service)
    {
        parent::__construct($service, RoleList::class, RoleForm::class, '', StoreRole::class, UpdateRole::class);
    }

    /**
     * @param array $input
     *
     * @return array
     */
    protected function processInput(array $input): array
    {
        $input['user'] = [
            'id' => _getCurrentUser('api')->mapDetail(['id'])->id
        ];

        return $input;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function entityList()
    {
        if($this->hasPermission('read') === false && $this->hasPermission('readOwn')) {
            return response()->json([], 403);
        }

        $entityList = mEntity::where('status', '=', true)->get()->map(function($entity) {
            return [
                'id'    => (int)$entity->id,
                'alias' => str_replace(['App\Http\Controllers\Admin\\', 'Controller'], '', $entity->controller),
                'name'  => $entity->name
            ];
        })->toArray();

        return response()->json([
            'entityList' => $entityList
        ]);
    }
}
