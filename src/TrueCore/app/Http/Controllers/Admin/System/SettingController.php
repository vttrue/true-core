<?php

namespace TrueCore\App\Http\Controllers\Admin\System;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TrueCore\App\Http\Controllers\Admin\Traits\Setting as SettingTrait;
use TrueCore\App\Libraries\Config;
use Illuminate\Support\{
    Facades\Artisan,
    Facades\Auth
};
use InvalidArgumentException;
use TrueCore\App\Http\Controllers\Admin\Base\Controller;

use TrueCore\App\Services\System\Setting as SettingService;
use Illuminate\Support\Facades\{
    Validator
};
use TrueCore\App\Http\Requests\Admin\System\UpdateSetting;

/**
 * Class SettingController
 *
 * @package TrueCore\App\Http\Controllers\Admin\System
 */
class SettingController extends Controller
{
    use SettingTrait;

    protected Config $setting;

    /**
     * SettingController constructor.
     *
     * @param SettingService $service
     */
    public function __construct(SettingService $service)
    {
        parent::__construct($service, '', '', '', '', UpdateSetting::class);

        $this->setting = Config::getInstance();
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Server error occurred'
            ], 500);
        }

        return response()->json([
            'message' => 'Cache has been cleared'
        ]);
    }
}
