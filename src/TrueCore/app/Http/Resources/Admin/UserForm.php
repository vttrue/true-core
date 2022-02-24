<?php

namespace TrueCore\App\Http\Resources\Admin\System;

use Illuminate\Http\Resources\Json\JsonResource;

class UserForm extends JsonResource
{
    public function toArray($request)
    {
        /* @var \TrueCore\App\Services\System\UserStructure $item */
        $item = $this->resource;

        return [
            'id'     => $item['id'],
            'role'   => array_filter($item['role'], fn($k) => ($k !== 'permissions'), ARRAY_FILTER_USE_KEY),
            'name'   => $item['name'],
            'phone'  => $item['phone'],
            'email'  => $item['email'],
            'status' => $item['status'],
        ];
    }
}
