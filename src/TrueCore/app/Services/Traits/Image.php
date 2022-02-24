<?php

namespace TrueCore\App\Services\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as ImageFacade;
use Hhxsv5\PhpMultiCurl\Curl;

/**
 * Trait Image
 *
 * @package TrueCore\App\Services\Traits
 */
trait Image
{
    /**
     * @param string $tempImagePath
     * @param string $destinationDir
     * @param bool $replace
     *
     * @return string
     *
     * @throws \Exception
     */
    protected static function saveImageSimple(string $tempImagePath, string $destinationDir, bool $replace = true): string
    {
        if(strpos($tempImagePath, 'http://') !== false || strpos($tempImagePath, 'https://') !== false) {
            $tempImagePath = static::downloadImage($tempImagePath);
        }

        if ($tempImagePath === '' || !Storage::disk('image')->has($tempImagePath)) {
            throw new \Exception('Temp image file at ' . $tempImagePath . ' does not exist');
        }

        $mimeList = [
            'image/jpg'     => 'jpg',
            'image/jpeg'    => 'jpg',
            'image/gif'     => 'gif',
            'image/webp'    => 'webp',
            'image/png'     => 'png',
            'image/svg+xml' => 'svg',
            'image/svg'     => 'svg'
        ];

        $mimeType = Storage::disk('image')->mimeType($tempImagePath);

        if(!array_key_exists($mimeType, $mimeList)) {
            throw new \Exception('Temp image file at ' . $tempImagePath . ' is not an image');
        }

        $fileName   = pathinfo($tempImagePath, PATHINFO_FILENAME);
        $ext        = $mimeList[$mimeType];

        $newPath = $destinationDir . '/' . $fileName . '.' . $ext;

        if ($newPath !== $tempImagePath) {

            while ($replace === true || Storage::disk('image')->has($newPath)) {
                if (Storage::disk('image')->has($newPath)) {

                    if ($replace === true) {
                        Storage::disk('image')->delete($newPath);
                        break;
                    } else {
                        $newPath = $destinationDir . '/' . $fileName . '-' . mt_rand(0, 1000) . '.' . $ext;
                    }

                } else {
                    break;
                }
            }

            Storage::disk('image')->copy($tempImagePath, $newPath);
        }

        return $newPath;
    }

    /**
     * @deprecated
     *
     * @param Model $mInstance
     * @param string $tempImagePath
     * @param bool $mainImage
     * @param bool $ogImage
     * @param string $subPath
     *
     * @throws \Exception
     *
     * @return string
     *
     * @TODO: refactor to be used within service classes only requiring just model's primary key value | deprecator @ 2018-10-24
     */
    protected static function _saveImage(Model $mInstance, string $tempImagePath, bool $mainImage = true, bool $ogImage = false, string $subPath = 'preview')
    {
        $classNameParts = explode('\\', get_class($mInstance));
        $classNameParts = array_splice($classNameParts, (count($classNameParts) - 2));
        $imageDir       = strtolower(implode('_', $classNameParts));

        $destinationDir = $imageDir . '/' . $mInstance->getKey() . '/' . (($subPath !== '') ? $subPath : 'preview');

        return self::saveImageSimple($tempImagePath, $destinationDir);
    }

    /**
     * @param string $tempImagePath
     * @param string $subPath
     * @param bool $replace
     *
     * @return string
     *
     * @throws \Exception
     */
    public function saveImage(string $tempImagePath, string $subPath = 'preview', $replace = true)
    {

        $classNameParts = explode('\\', static::class);
        $classNameParts = array_splice($classNameParts, (count($classNameParts) - 2));
        $imageDir       = strtolower(implode('_', $classNameParts));

        $destinationDir = $imageDir . '/' . (($subPath !== '') ? $subPath : 'preview');

        return static::saveImageSimple($tempImagePath, $destinationDir, $replace);
    }

    /**
     * @param array $imageFields
     */
    public function saveImages(array $imageFields) : void
    {
        if(count($imageFields) > 0) {
            $imageFieldExtractor = function(array $structure, string $field) {

                $imageFieldValue = null;

                $imageField         = $field;
                $imageFieldValue    = $structure;
                $imageFieldParent   = [];

                $resultStructure    = [];
                $rsPointer          = &$resultStructure;

                $skipped = 0;

                while (($imageField = substr($imageField, 0, $lastPos = ((($lastPos = strpos($imageField, '.')) !== false) ? $lastPos : strlen($imageField)))) !== '') {

                    // Indeed cheaper than checking for $rsPointer not being empty. Really.
                    if ($skipped > 0) {
                        $imageFieldParent = $imageFieldValue;
                    }

                    $imageFieldValue = ((is_array($imageFieldValue) && array_key_exists($imageField, $imageFieldValue) && is_array($imageFieldValue[$imageField])) ? $imageFieldValue[$imageField] : null);

                    if ($imageFieldValue === null) {
                        $imageFieldValue = ['image' => null];
                        //break;
                    }

                    // In order to avoid losing identification of nested elements that have images
                    // We have no idea of what exact field identifies such element
                    // So we simply pass the whole upstream structure to be updated along with the images
                    $rsPointer              = array_merge($rsPointer, $imageFieldParent);
                    $rsPointer[$imageField] = [];
                    $rsPointer              = &$rsPointer[$imageField];

                    $skipped    += ($lastPos + 1);
                    $imageField = substr($field, $skipped);
                }

                //$rsPointer = $imageFieldValue;

                $result = null;

                if (is_array($imageFieldValue)) {
                    $result = [
                        'value'     => &$rsPointer,
                        'structure' => $resultStructure
                    ];
                    $result['value'] = $imageFieldValue;
                }

                return $result;
            };

            $resultImageFields = [];

            $mappedStructure = $this->mapDetail(array_merge(array_map(function($v) {
                return ((strpos($v, '.') !== false) ? explode('.', $v)[0] : $v);
            }, array_keys($imageFields)), ['id']))->toArray();

            foreach($imageFields AS $imageField => $imageFieldValue) {

                $currentImageFieldValue = $imageFieldExtractor($mappedStructure, $imageField);

                if (is_array($imageFieldValue) && array_key_exists('image', $imageFieldValue) && ((is_string($imageFieldValue['image']) && $imageFieldValue['image'] !== '') || $imageFieldValue['image'] === null)) {

                    if (is_array($currentImageFieldValue) && array_key_exists('image', $currentImageFieldValue['value']) && $currentImageFieldValue['value']['image'] !== $imageFieldValue['image']) {

                        if(is_string($imageFieldValue['image'])) {

                            try {

                                $image = $this->saveImage($imageFieldValue['image'], ((is_string($mappedStructure['id']) || is_numeric($mappedStructure['id'])) ? (string) $mappedStructure['id'] :
                                    ''), false);

                                [$width, $height] = getimagesize(Storage::disk('image')->url($image));

                            } catch (\Throwable $e) {

                                $image  = null;
                                $width  = null;
                                $height = null;
                            }
                        } else {
                            $image  = null;
                            $width  = null;
                            $height = null;
                        }

                        $currentImageFieldValue['value']['image'] = [
                            'image' => $image,
                            'width' => $width,
                            'height' => $height
                        ];

                        $resultImageFields  = array_merge($resultImageFields, $currentImageFieldValue['structure']);
                        $mappedStructure    = array_merge($mappedStructure, $resultImageFields);
                    }

                }

            }
//dd($imageFields, $resultImageFields, $mappedStructure);
//print_r([$imageFields, $resultImageFields, $mappedStructure]);die;
            if(count($resultImageFields) > 0) {
                $this->edit($resultImageFields);
            }

        }
    }

    /**
     * @param string $imageUrl
     * @return bool|string
     */
    protected static function downloadImage(string $imageUrl)
    {
        try {
            $fileName = substr(md5(mt_rand(0, 9999) . time()), 0, 8);

            // @TODO: get rid of this, lol | Deprecator @ 2020-06-26
            $uploadPath = _getCurrentUser()->id ?? 'default' . '/';

            if (!$uploadPath) {
                return false;
            }

            $curl = new Curl(null, [
                CURLOPT_TIMEOUT => 30
            ]);

            $curl->makeGet($imageUrl);

            $response = $curl->exec();

            if(in_array($response->getHttpCode(), [200, 206])) {

                $image      = ImageFacade::make($response->getBody());
                $mimeType   = $image->mime();

                $mimeTypeList = [
                    'image/jpeg'    => 'jpg',
                    'image/jpg'     => 'jpg',
                    'image/gif'     => 'gif',
                    'image/png'     => 'png',
                    'image/svg'     => 'svg'
                ];

                if(!array_key_exists($mimeType, $mimeTypeList)) {
                    return false;
                }

                Storage::disk('image')->put('temp/' . $uploadPath . $fileName . '.' . $mimeTypeList[$mimeType], (string)$image->encode());

                return 'temp/' . $uploadPath . $fileName . '.' . $mimeTypeList[$mimeType];

            }

            return false;

        } catch (\Throwable $e) {

            return '';
        }
    }
}
