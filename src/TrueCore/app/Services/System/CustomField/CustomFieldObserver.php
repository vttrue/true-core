<?php

namespace TrueCore\App\Services\System\CustomField;

use TrueCore\App\Services\Observer;

/**
 * Class CustomFieldObserver
 *
 * @package App\Services\System\CustomField
 */
class CustomFieldObserver extends Observer
{
    protected array $data = [];

    /**
     * CustomFieldObserver constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
