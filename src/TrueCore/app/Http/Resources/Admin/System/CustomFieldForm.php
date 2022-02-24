<?php

namespace TrueCore\App\Http\Resources\Admin\System;

use Illuminate\Http\Resources\Json\JsonResource;
use TrueCore\App\Http\Resources\Api\Traits\Adjustable;

/**
 * Class CustomFieldForm
 *
 * @package App\Http\Resources\Admin\System
 */
class CustomFieldForm extends JsonResource
{
    use Adjustable;

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Services\System\CustomField\CustomFieldStructure|array $item */
        $item = $this->resource;

        return $item->toArray();
    }
}
