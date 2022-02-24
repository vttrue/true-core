<?php

namespace TrueCore\App\Http\Controllers\Api;

/**
 * Interface ApiController
 *
 * @package TrueCore\App\Http\Controllers\Api
 */
interface ApiController
{
    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItem($id);

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItemList();
}