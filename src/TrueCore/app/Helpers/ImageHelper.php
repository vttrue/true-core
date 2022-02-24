<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 28.01.2021
 * Time: 12:10
 */

namespace TrueCore\App\Helpers;

use Illuminate\Support\Facades\Storage;

/**
 * Class ImageHelper
 *
 * @package TrueCore\App\Helpers
 */
class ImageHelper
{
    /**
     * @param array $fieldValue
     * @param string $type
     * @param int|null $width
     * @param int|null $height
     * @param string $gravity
     * @param bool $returnOriginalIfNoneFound
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function getImageField(array $fieldValue, string $type = 'resize', ?int $width = null, ?int $height = null, string $gravity = 'center', bool $returnOriginalIfNoneFound = false) : array
    {
        if ((array_key_exists('image', $fieldValue) && (is_string($fieldValue['image']) || $fieldValue['image'] === null) && array_key_exists('thumbs', $fieldValue) && is_array($fieldValue['thumbs'])) === false) {
            throw new \Exception('Trying to get invalid image field');
        }

        $thumbEntry = null;

        if ($fieldValue['image'] !== null) {

            // In case this is an svg image. We don't need any generated previews in such case.
            if (strrpos($fieldValue['image'], '.svg') === (strlen($fieldValue['image']) - 4)) {
                return [
                    'image'        => $fieldValue['image'],
                    'thumb'        => Storage::disk('image')->url($fieldValue['image']),
                    'thumb2x'      => Storage::disk('image')->url($fieldValue['image']),
                    'realWidth'    => null,
                    'realHeight'   => null,
                    'realWidth2x'  => null,
                    'realHeight2x' => null,
                ];
            }

            $thumbEntry = static::extractThumb($fieldValue['thumbs'], $type, $width, $height, $gravity);

            if ($thumbEntry === null && $returnOriginalIfNoneFound === true) {

                $thumbEntry = static::extractThumb($fieldValue['thumbs'], 'resize', null, null, 'center');

                if ($thumbEntry === null) {

                    $thumbUrl = Storage::disk('image')->url($fieldValue['image']);

                    return [
                        'image'        => $fieldValue['image'],
                        'thumb'        => $thumbUrl,
                        'thumb2x'      => $thumbUrl,
                        'realWidth'    => ((array_key_exists('width', $fieldValue) && is_numeric($fieldValue['width'])) ? $fieldValue['width'] : null),
                        'realHeight'   => ((array_key_exists('height', $fieldValue) && is_numeric($fieldValue['height'])) ? $fieldValue['height'] : null),
                        'realWidth2x'  => ((array_key_exists('width', $fieldValue) && is_numeric($fieldValue['width'])) ? $fieldValue['width'] : null),
                        'realHeight2x' => ((array_key_exists('height', $fieldValue) && is_numeric($fieldValue['height'])) ? $fieldValue['height'] : null)
                    ];
                }
            }
        }

        $thumb   = null;
        $thumb2x = null;

        $realWidth  = null;
        $realHeight = null;

        $realWidth2x  = null;
        $realHeight2x = null;

        if (is_array($thumbEntry)) {
            $thumb   = ((array_key_exists('thumb', $thumbEntry) && is_string($thumbEntry['thumb']) && $thumbEntry['thumb'] !== '') ? Storage::disk('imageCache')->url($thumbEntry['thumb']) : null);
            $thumb2x = ((array_key_exists('thumb2x', $thumbEntry) && is_string($thumbEntry['thumb2x']) && $thumbEntry['thumb2x'] !== '') ? Storage::disk('imageCache')->url($thumbEntry['thumb2x']) : null);

            $realWidth  = $thumbEntry['realWidth'];
            $realHeight = $thumbEntry['realHeight'];

            $realWidth2x  = $thumbEntry['realWidth2x'];
            $realHeight2x = $thumbEntry['realHeight2x'];
        }

        $thumb   = (($thumb !== null) ? $thumb : Storage::disk('image')->url('no_image.svg'));
        $thumb2x = (($thumb2x !== null) ? $thumb2x : Storage::disk('image')->url('no_image.svg'));

        return [
            'image'        => $fieldValue['image'],
            'thumb'        => $thumb,
            'thumb2x'      => $thumb2x,
            'realWidth'    => $realWidth,
            'realHeight'   => $realHeight,
            'realWidth2x'  => $realWidth2x,
            'realHeight2x' => $realHeight2x,
        ];
    }

    /**
     * @param array $thumbList
     * @param string $type
     * @param int|null $width
     * @param int|null $height
     * @param string $gravity
     *
     * @return array|null
     */
    protected static function extractThumb(array $thumbList, string $type = 'resize', ?int $width = null, ?int $height = null, string $gravity = 'center'): ?array
    {
        $thumb = null;

        if (count($thumbList) > 0) {
            foreach ($thumbList AS $thumbEntry) {

                $thumbEntry += [
                    'thumb'   => null,
                    'thumb2x' => null,
                    'type'    => 'resize',
                ];

                $thumbGravity = ((is_array($thumbEntry['params']) && array_key_exists('gravity', $thumbEntry['params'])) ? $thumbEntry['params']['gravity'] : 'center');

                if (is_array($thumbEntry) && $thumbEntry['type'] === $type && $thumbEntry['width'] === $width && $thumbEntry['height'] === $height && $gravity === $thumbGravity) {
                    $thumb = $thumbEntry;
                    break;
                }

            }
        }

        return $thumb;
    }
}
