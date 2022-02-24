<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 22.10.2019
 * Time: 17:45
 */

namespace TrueCore\App\Services\System;

use \TrueCore\App\Services\Observer;

class UserObserver extends Observer
{
    protected array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
