<?php

namespace TrueCore\App\Http\Controllers\Admin\Common;

use \TrueCore\App\Http\Controllers\Controller;

use \TrueCore\App\Http\Requests\Admin\Common as reqCommon;

use \TrueCore\App\Libraries as Lib;

class UploadTempDocController extends Controller
{
    public function uploadTempDoc(reqCommon\UploadDoc $request)
    {
        return response()->json(
            Lib\TempDoc::getInstance()->upload(
                $request,
                ($request->post('temp', 'true') === 'true'),
                $request->post('path', ''),
                $request->post('fileName', '')
            )
        );
    }
}
