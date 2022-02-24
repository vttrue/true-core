<?php

namespace TrueCore\App\Services\System;

use TrueCore\App\Services\Structure;

/**
 * Class SettingStructure
 *
 * @property int $id
 * @property string $group
 * @property string $key
 * @property array|string|null $value
 * @property string|null $createdAt
 * @property string|null $updatedAt
 *
 * @package TrueCore\App\Services\System
 */
class SettingStructure extends Structure
{
    /**
     * SettingStructure constructor.
     *
     * @param SettingRepository $repository
     */
    public function __construct(SettingRepository $repository)
    {
        parent::__construct($repository);
    }
}
