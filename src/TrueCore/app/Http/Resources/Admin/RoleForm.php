<?php

namespace TrueCore\App\Http\Resources\Admin\System;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleForm extends JsonResource
{
    public function toArray($request)
    {
        /** @var \TrueCore\App\Services\System\RoleStructure $item */
        $item = $this->resource;

        return [
            'id'            => $item['id'],
            'name'          => $item['name'],
            'permissions'   => array_filter($item['permissions'], function($item) {
                return ($item['status'] === true);
            }),
        ];
    }
}
