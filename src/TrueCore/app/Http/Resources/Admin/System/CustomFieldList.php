<?php

namespace TrueCore\App\Http\Resources\Admin\System;

use TrueCore\App\Http\Resources\Api\Traits\Adjustable;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class CustomFieldList
 *
 * @package App\Http\Resources\Admin\System
 */
class CustomFieldList extends JsonResource
{
    use Adjustable;

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $result = [];

        /* @var TrueCore\App\Services\System\CustomField\CustomFieldStructure[] $items */
        $items = $this->resource;

        foreach ($items AS $item) {
            $result[] = $item->toArray();
        }

        return $result;
    }
}
