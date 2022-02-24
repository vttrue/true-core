<?php

namespace TrueCore\App\Http\Controllers\Admin\Common;

use \TrueCore\App\Http\Controllers\Controller;

use \TrueCore\App\Http\Requests\Admin\Common as reqCommon;

use \TrueCore\App\Libraries as Lib;

class UploadTempImageController extends Controller
{
    public function uploadTempImage(reqCommon\UploadImage $request)
    {
        return response()->json(
            Lib\TempImage::getInstance()->upload(
                $request,
                $request->post('w'),
                $request->post('h'),
                true,
                ($request->post('temp', 'true') === 'true'),
                $request->post('path', '')
            )
        );
    }
}
