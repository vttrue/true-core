<?php

namespace TrueCore\App\Libraries;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Class TempDoc
 *
 * @package App\Libraries
 */
class TempDoc
{
    private static $_instance = null;

    private static array $mimeTypes = [
        'docx' => [
            'application/vnd.ms-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'doc'  => [
            'application/msword',
            'application/vnd.ms-word',
        ],
        'dot'  => 'application/msword',
        'odt'  => 'application/vnd.oasis.opendocument.text',
        'odx'  => [
            'application/vnd.oasis.opendocument.text',
        ],
        'pdf'  => 'application/pdf',
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
        'xml'  => 'application/vnd.ms-excel',
        'xls'  => [
            'application/vnd.ms-excel',
            'application/excel',
            'application/msexcel',
            'application/msexcell',
            'application/x-dos_ms_excel',
            'application/x-excel',
            'application/x-ms-excel',
            'application/x-msexcel',
            'application/x-xls',
            'application/xls',
        ],
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt'  => 'text/plain',
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
     * @return TempDoc
     */
    public static function getInstance()
    {
        if ( !(self::$_instance instanceof TempDoc) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @param Request     $request
     * @param bool        $temp
     * @param string      $path
     * @param null|string $fileName
     *
     * @return array
     * @throws \Exception
     */
    public static function upload(Request $request, bool $temp = true, string $path = '', ?string $fileName = null): array
    {
        $file = $request->file('file');

        $ext = null;
        $mimeType = $file->getMimeType();
        $extOrigin = $file->getClientOriginalExtension();

        foreach (self::$mimeTypes as $mimeTypeExt => $mimeTypes) {
            if ( (is_array($mimeTypes) && in_array(strtolower($mimeType), $mimeTypes)) || (is_string($mimeTypes) && strtolower($mimeType) === $mimeTypes) ) {
                if ( strtolower($extOrigin) === strtolower($mimeTypeExt) ) {
                    $ext = $mimeTypeExt;
                    break;
                } else {
                    $ext = $mimeTypeExt;
                }
            }
        }

        if ( strpos($ext, 'php') !== false ) {
            throw new \Exception('Uploading something like this is not quite a good idea, indeed.');
        }

        if ( $ext !== null ) {

            if ( $fileName === null ) {
                $fileName = substr(md5(mt_rand(0, 9999) . time()), 0, 8);
            }

            $fileName .= '.' . $ext;

            if ( $temp === true ) {

                $uploadPath = str_replace('.', '', trim($path, " \t\n\r \v /"));


                $uploadedFileDir = 'temp/' . $uploadPath;

                $file->storeAs($uploadedFileDir, $fileName, 'doc');

            } elseif ( $path !== '' ) {

                $path = str_replace('.', '', $path);

                $uploadedFileDir = trim($path, " \t\n\r \v /");

                $file->storeAs($uploadedFileDir, $fileName, 'doc');

            } else {
                throw new \Exception('Upload file must either be set as temporary or upload path must be specified.');
            }

            $relativePath = $uploadedFileDir . $fileName;

            $fileUrl = Storage::disk('doc')->url($relativePath);
            $filePath = Storage::disk('doc')->path($relativePath);

            return [
                'relativePath' => $relativePath,
                'url'          => $fileUrl,
                'fileSize'     => Storage::disk('doc')->size($relativePath),
                'mimeType'     => $mimeType,
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

        $tempImages = Storage::disk('doc')->files('temp/' . $clearPath, true);

        if ( !empty($tempImages) ) {
            Storage::disk('doc')->delete($tempImages);
        }

        $cacheTempImages = Storage::disk('doc')
                                  ->files('cache/temp/' . $clearPath);

        if ( !empty($cacheTempImages) ) {
            Storage::disk('doc')->delete($cacheTempImages);
        }
    }
}
