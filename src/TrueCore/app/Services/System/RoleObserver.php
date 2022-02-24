<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 22.10.2019
 * Time: 16:49
 */

namespace TrueCore\App\Services\System;

use \TrueCore\App\Services\Observer;

class RoleObserver extends Observer
{
    protected array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
