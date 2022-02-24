<?php

namespace TrueCore\App\Services\System\CustomField;

use TrueCore\App\Services\Factory;
use TrueCore\App\Models\System\CustomField as CustomFieldModel;

/**
 * Class CustomFieldFactory
 *
 * @package App\Services\System\CustomField
 */
class CustomFieldFactory extends Factory
{
    /**
     * @return CustomField
     * @throws \Exception
     */
    public function create()
    {
        return new CustomField(new CustomFieldRepository(new CustomFieldModel), $this, new CustomFieldObserver);
    }
}
