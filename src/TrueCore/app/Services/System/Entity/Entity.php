<?php

namespace TrueCore\App\Services\System\Entity;

use TrueCore\App\Services\Service;

/**
 * Class User
 *
 * @method EntityRepository getRepository()
 *
 * @method static EntityStructure[] mapList(array $items, ?array $fields = null)
 * @method EntityStructure mapDetail(?array $fields = null)
 *
 * @method static Entity|null add(array $data)
 *
 * @method static Entity|null getOne(array $conditions = [])
 * @method static Entity[] getAll(array $options = [], array $columns = ['*'])
 * @method static Entity[]|null getRandom(array $conditions = [], array $options = [])
 *
 * @package \TrueCore\App\Services\System
 */
class Entity extends Service
{

    /**
     * Entity constructor.
     *
     * @param null|EntityRepository $repository
     * @param null|EntityFactory    $factory
     * @param null|EntityObserver   $observer
     *
     * @throws \Exception
     */
    public function __construct(EntityRepository $repository = null, EntityFactory $factory = null, EntityObserver $observer = null)
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
     * @param string $field
     *
     * @return bool
     * @throws \Exception
     */
    public function switch(string $field): bool
    {
        return parent::switch($field);
    }

    /**
     * @return EntityStructure
     */
    protected function getStructureInstance(): EntityStructure
    {
        return new EntityStructure($this->getRepository());
    }
}
