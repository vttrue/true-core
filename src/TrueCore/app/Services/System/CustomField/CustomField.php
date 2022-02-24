<?php

namespace TrueCore\App\Services\System\CustomField;

use TrueCore\App\Services\Service;

/**
 * Class CustomField
 *
 * @method CustomFieldRepository getRepository()
 *
 * @method static CustomFieldStructure[] mapList(array $items, ?array $fields = null)
 * @method CustomFieldStructure mapDetail(?array $fields = null)
 *
 * @method static CustomField|null add(array $data)
 *
 * @method static CustomField|null getOne(array $conditions = [])
 * @method static CustomField[] getAll(array $options = [], array $columns = ['*'])
 * @method static CustomField|null getRandom(array $conditions = [], array $options = [])
 *
 * @package App\Services\System\CustomField
 */
class CustomField extends Service
{
    /**
     * CustomField constructor.
     *
     * @param CustomFieldRepository|null $repository
     * @param CustomFieldFactory|null $factory
     * @param CustomFieldObserver|null $observer
     *
     * @throws \Exception
     */
    public function __construct(CustomFieldRepository $repository = null, CustomFieldFactory $factory = null, CustomFieldObserver $observer = null)
    {
        parent::__construct($repository, $factory, $observer);
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function normalizeData(array $data): array
    {
        return $data;
    }

    /**
     * @param array $data
     * @throws \Exception
     */
    protected function postProcessor(array $data): void
    {
        //
    }

    /**
     * @param bool $soft
     * @return bool
     * @throws \TrueCore\App\Services\Traits\Exceptions\ModelDeleteException
     */
    public function delete(bool $soft = false): bool
    {
        return $this->getRepository()->delete($soft);
    }

    /**
     * @return CustomFieldStructure
     */
    protected function getStructureInstance(): CustomFieldStructure
    {
        return new CustomFieldStructure($this->getRepository());
    }
}
