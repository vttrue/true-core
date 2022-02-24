<?php

namespace TrueCore\App\Http\Resources\Admin\System;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleList extends JsonResource
{
    public function toArray($request)
    {
        /** @var \TrueCore\App\Services\System\RoleStructure[] $items */
        $items = $this->resource;

        $result = [];

        foreach($items AS $item) {
            $result[] = [
                'id'            => $item['id'],
                'name'          => $item['name'],
                'permissions'   => array_filter($item['permissions'], function($item) {
                    return ($item['status'] === true);
                }),
            ];
        }

        return $result;
    }
}
