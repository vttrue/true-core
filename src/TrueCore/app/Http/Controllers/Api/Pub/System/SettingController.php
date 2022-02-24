<?php

namespace TrueCore\App\Http\Controllers\Api\Pub\System;

use Illuminate\Support\Carbon;
use TrueCore\App\Http\Controllers\Controller;
use TrueCore\App\Libraries\Config;
use TrueCore\App\Services\System\Setting as sSetting;

/**
 * Class SettingController
 *
 * @package TrueCore\App\Http\Controllers\Api\Pub\System
 */
class SettingController extends Controller
{
    /**
     * @param $group
     * @param $key
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function getItem($group, $key = null)
    {
        if (in_array($group, ['images', 'watermark'])) {
            return response()->json(['errors' => ['info' => 'You can\'t get content of that value']], 422);
        }

        $this->data['data'] = Config::getInstance()->get($key, $group);

        return $this->response();
    }

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function getItemList()
    {
        $settings = sSetting::map(Config::getInstance()->get());

        $this->data['settings'] = array_filter($settings, function($v, $k) {
            return !in_array($k, ['images', 'watermark']);
        }, ARRAY_FILTER_USE_BOTH);

        $this->data['entities'] = $this->getAvailableEntities();

        return $this->response();
    }

    /**
     * @param $group
     * @param $key
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function getTime()
    {
        $this->data['time'] = Carbon::now()->timezone(0)->format('Y/m/d H:i:s');

        return $this->response();
    }
}
