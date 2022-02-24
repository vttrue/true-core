<?php

namespace TrueCore\App\Libraries;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TempImage
{
    private static $_instance = null;

    private static array $mimeTypes = [
        'gif'  => 'image/gif',
        'jpg'  => [
            'image/jpeg',
            'image/jpg',
        ],
        'webp' => 'image/webp',
        'png'  => 'image/png',
        'svg'  => [
            'image/svg',
            'image/svg+xml',
        ],
    ];

    protected function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    private function __wakeup()
    {
        //
    }

    /**
     * @return TempImage
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof TempImage)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * @param Request $request
     * @param int $width
     * @param int $height
     * @param bool $cached
     * @param bool $temp
     * @param string $path
     * @return array
     * @throws \Exception
     */
    public static function upload(Request $request, $width = 120, $height = 120, bool $cached = true, bool $temp = true, string $path = ''): array
    {
        $file = $request->file('image');

        $ext = null;

        $mimeType = $file->getMimeType();
        $extOrigin = $file->getClientOriginalExtension();

        foreach (self::$mimeTypes as $mimeTypeExt => $mimeTypes) {
            if ((is_array($mimeTypes) && in_array(strtolower($mimeType), $mimeTypes)) || (is_string($mimeTypes) && strtolower($mimeType) === $mimeTypes)) {
                if (strtolower($extOrigin) === strtolower($mimeTypeExt)) {
                    $ext = $mimeTypeExt;
                    break;
                } else {
                    $ext = $mimeTypeExt;
                }
            }
        }

        if ($ext !== null) {

            if (strpos($ext, 'php') !== false) {
                throw new \Exception('Uploading something like this is not quite a good idea, indeed.');
            }

            $fileName = substr(md5(mt_rand(0, 9999) . time()), 0, 8)
                . '.' . $ext;

            if ($temp === true) {

                if ($path !== '') {
                    $uploadPath = str_replace('.', '', trim($path, " \t\n\r \v /"));
                } else {
                    /** @TODO: для обратной совместимости. Если нет id у юзера (если eloquent-аутентификация), то берём маппинг сервиса. Incarnator | 2020-06-17 */
                    $uploadPath = _getCurrentUser('api')->id ?? _getCurrentUser('api')->mapDetail(['id'])->id;
                }

                $uploadedFileDir = 'temp/' . $uploadPath;

                $file->storeAs($uploadedFileDir, $fileName, 'image');

            } else if ($path !== '') {

                $path = str_replace('.', '', $path);

                $uploadedFileDir = trim($path, " \t\n\r \v /");

                $file->storeAs($uploadedFileDir, $fileName, 'image');

            } else {
                throw new \Exception('Upload image must either be set as temporary or upload path must be specified.');
            }

            $imageUrl = Storage::disk('image')->url($uploadedFileDir . '/' . $fileName);

            return [
                'image'     => $uploadedFileDir . '/' . $fileName,
                'imagePath' => $imageUrl,
                'thumb'     => $imageUrl,
                'thumb2x'   => $imageUrl,
            ];
        } else {
            return [
                'message' => 'Error format download file, please contact developers',
                'status'  => false,
            ];
        }
    }

    public static function clear()
    {
        //        $clearPath = _getCurrentUser('api')->id;
        /** @TODO: для обратной совместимости. Если нет id у юзера (если eloquent-аутентификация), то берём маппинг сервиса. Incarnator | 2020-06-17 */
        $clearPath = _getCurrentUser('api')->id ?? _getCurrentUser('api')->mapDetail(['id'])->id;

        $tempImages = Storage::disk('image')->files('temp/' . $clearPath, true);

        if (!empty($tempImages)) {
            Storage::disk('image')->delete($tempImages);
        }

        $cacheTempImages = Storage::disk('image')
            ->files('cache/temp/' . $clearPath);

        if (!empty($cacheTempImages)) {
            Storage::disk('image')->delete($cacheTempImages);
        }
    }
}
