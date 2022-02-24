<?php

namespace TrueCore\App\Http\Controllers\Admin\System;

use TrueCore\App\Http\Controllers\Admin\Base\Controller as AdminController;
use TrueCore\App\Http\Requests\Admin\System\StoreCustomField;
use TrueCore\App\Http\Requests\Admin\System\UpdateCustomField;
use TrueCore\App\Http\Resources\Admin\System\CustomFieldForm;
use TrueCore\App\Http\Resources\Admin\System\CustomFieldList;
use TrueCore\App\Services\System\CustomField\CustomField as CustomFieldService;

/**
 * Class CustomFieldController
 *
 * @package TrueCore\App\Http\Controllers\Admin\System
 */
class CustomFieldController extends AdminController
{
    /**
     * CustomFieldController constructor.
     *
     * @param CustomFieldService $service
     */
    public function __construct(CustomFieldService $service)
    {
       parent::__construct($service, CustomFieldList::class, CustomFieldForm::class, '', StoreCustomField::class, UpdateCustomField::class);
    }

    /**
    * @param array $input
    *
    * @return array
    */
    protected function processInput(array $input): array
    {
        return $input;
    }
}
