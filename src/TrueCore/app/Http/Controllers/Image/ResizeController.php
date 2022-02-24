<?php

namespace TrueCore\App\Http\Controllers\Image;

use TrueCore\App\Http\Controllers\Controller;

use TrueCore\App\Http\Requests\Image\StoreImagePreview;
use TrueCore\App\Libraries\ImageResizeManager\ImageResizeManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{
    Log,
    Validator
};

/**
 * Class ResizeController
 *
 * @package TrueCore\App\Http\Controllers\Image
 */
class ResizeController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function store(Request $request)
    {
        $request    = StoreImagePreview::createFrom($request);
        $validator  = Validator::make($request->all(), $request->rules(), $request->messages());

        if ($validator->fails() === true) {

            Log::channel('imageResize')->info([
                'body'      => json_encode($request->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'headers'   => json_encode($request->headers->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'errors'    => json_encode($validator->errors(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ]);

            $this->data = [
                'error'  => 'Wrong parameters passed',
                'errors' => $validator->errors(),
            ];

            return $this->response(422);
        }

        if (config('app.debug') === true) {
            Log::channel('imageResize')->info([
                'body'    => json_encode($request->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'headers' => json_encode($request->headers->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ]);
        }

        $chunkList = $request->all();

        if (count($chunkList) > 0) {

            foreach ($chunkList AS $chunk) {

                $additionalData = $chunk['additionalData'];

                $thumbList = (new ImageResizeManager('trueResizer'))->processResult($chunk);

                if (count($thumbList) > 0) {

                    $errorMessage = null;

                    if (is_array($additionalData) && count($additionalData) > 0) {

                        if (
                            array_key_exists('imageInfo', $additionalData) &&
                            is_array($additionalData['imageInfo']) &&
                            array_key_exists('entity', $additionalData['imageInfo']) &&
                            is_string($additionalData['imageInfo']['entity']) &&
                            trim($additionalData['imageInfo']['entity']) !== '' &&
                            array_key_exists('key', $additionalData['imageInfo']) &&
                            (is_numeric($additionalData['imageInfo']['key']) || is_string($additionalData['imageInfo']['key'])) &&
                            array_key_exists('sourcePath', $additionalData['imageInfo']) &&
                            is_string($additionalData['imageInfo']['sourcePath']) &&
                            trim($additionalData['imageInfo']['sourcePath']) !== ''
                        ) {

                            try {
                                $entity = str_replace('\\\\', '\\', $additionalData['imageInfo']['entity'])::find($additionalData['imageInfo']['key']);
                            } catch (\Throwable $e) {
                                $entity = null;
                            }

                            if ($entity !== null && method_exists($entity, 'saveThumbs') === true) {

                                $entity->saveThumbs([
                                    'path'           => $additionalData['imageInfo']['sourcePath'],
                                    'additionalData' => $additionalData['imageInfo']
                                ], $thumbList);

                            } else {
                                $errorMessage = 'Invalid entity';
                            }

                        } else {
                            $errorMessage = 'Corrupted Additional-Data provided';
                        }

                    } else {
                        $errorMessage = 'Additional-Data missing';
                    }

                    if ($errorMessage !== null) {

                        $this->data = [
                            'error'   => $errorMessage
                        ];

                        return $this->response(422);
                    }
                }

            }

        }

        $this->data = [
            'success'   => true
        ];

        return $this->response(200);
    }
}
