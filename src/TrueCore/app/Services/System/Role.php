<?php

namespace TrueCore\App\Services\System;

use \TrueCore\App\Services\Service;

/**
 * Class Role
 *
 * @method RoleRepository getRepository()
 *
 * @method static RoleStructure[] mapList(array $items, ?array $fields = null)
 * @method RoleStructure mapDetail(?array $fields = null)
 *
 * @method static Role|null add(array $data)
 *
 * @method static Role|null getOne(array $conditions = [])
 * @method static Role[] getAll(array $options = [], array $columns = ['*'])
 * @method static Role[]|null getRandom(array $conditions = [], array $options = [])
 *
 * @package \TrueCore\App\Services\System
 */
class Role extends Service
{
    /**
     * @return array
     */
    public static function getActions()
    {
        return [
            [
                'name' => 'read',
                'title' => 'Чтение'
            ],
            [
                'name' => 'write',
                'title' => 'Запись'
            ],
            [
                'name'  => 'readOwn',
                'title' => 'Чтение (только свое)'
            ],
            [
                'name'  => 'writeOwn',
                'title' => 'Запись (только свое)'
            ]
        ];
    }

    /**
     * Role constructor.
     *
     * @param RoleRepository|null $repository
     * @param RoleFactory|null $factory
     * @param RoleObserver|null $observer
     *
     * @throws \Exception
     */
    public function __construct(RoleRepository $repository = null, RoleFactory $factory = null, RoleObserver $observer = null)
    {
        parent::__construct($repository, $factory, $observer);
    }

    /**
     * @param array $data
     */
    protected function postProcessor(array $data): void
    {
        //
    }

    /**
     * @return RoleStructure
     */
    protected function getStructureInstance(): RoleStructure
    {
        return new RoleStructure($this->getRepository());
    }
}