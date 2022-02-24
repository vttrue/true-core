<?php

namespace TrueCore\App\Services\System\Entity;

use \TrueCore\App\Services\Observer;

class EntityObserver extends Observer
{
    protected array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
