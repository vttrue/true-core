<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 23.06.2020
 * Time: 22:45
 */

namespace TrueCore\App\Services\System;

use \TrueCore\App\Services\Observer;

/**
 * Class SettingObserver
 *
 * @package TrueCore\App\Services\System
 */
class SettingObserver extends Observer
{
    protected array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
}
