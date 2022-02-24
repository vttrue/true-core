<?php

namespace TrueCore\App\Services\System\CustomField;

use TrueCore\App\Services\Structure;

/**
 * Class CustomFieldStructure
 *
 * @param int $id
 * @param string $code
 * @param array $entities
 * @param int $sortOrder
 * @param array|null $owner
 * @param bool $status
 * @param string|null $createdAt
 * @param string|null $updatedAt
 *
 * @package App\Services\System\CustomField
 */
class CustomFieldStructure extends Structure
{
    /**
     * CustomFieldStructure constructor.
     *
     * @param CustomFieldRepository $repository
     */
    public function __construct(CustomFieldRepository $repository)
    {
        parent::__construct($repository);
    }
}
