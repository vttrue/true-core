<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 23.06.2020
 * Time: 22:45
 */

namespace TrueCore\App\Services\System;

use \TrueCore\App\Services\Factory;
use \TrueCore\App\Models\System\Setting as SettingModel;

/**
 * Class SettingFactory
 *
 * @package TrueCore\App\Services\System
 */
class SettingFactory extends Factory
{
    /**
     * @return Setting
     * @throws \Exception
     */
    public function create()
    {
        return new Setting(new SettingRepository(new SettingModel), $this, new SettingObserver);
    }
}