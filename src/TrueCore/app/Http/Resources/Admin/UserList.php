<?php

namespace TrueCore\App\Http\Resources\Admin\System;

use Illuminate\Http\Resources\Json\JsonResource;

class UserList extends JsonResource
{
    public function toArray($request)
    {
        /** @var \TrueCore\App\Services\System\UserStructure[] $items */
        $items = $this->resource;

        $result = [];

        foreach($items AS $item) {
            $result[] = [
                'id'            => $item['id'],
                'name'          => $item['name'],
                'role'          => $item['role'],
                'phone'         => $item['phone'],
                'email'         => $item['email'],
                'status'        => $item['status'],
                'lastVisitAt'   => $item['lastVisitedAt'],
                'createdAt'     => $item['createdAt'],
                'updatedAt'     => $item['updatedAt']
            ];
        }

        return $result;
    }
}
