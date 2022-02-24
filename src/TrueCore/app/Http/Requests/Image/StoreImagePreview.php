<?php

namespace TrueCore\App\Http\Requests\Image;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreImagePreview
 *
 * @package TrueCore\App\Http\Requests\Image
 */
class StoreImagePreview extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            '*.key' => 'required|string|min:1|max:255',
            '*.path'    => 'required|string|min:1|max:500',
            '*.width'   => 'required|integer|min:1',
            '*.height'  => 'required|integer|min:1',
            '*.processed'   => 'required|array',
            '*.processed.*' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {

                    if (array_key_exists('type', $value) === false) {
                        $fail($attribute . '.type must be present');
                    } else {

                        if (is_string($value['type']) === false || in_array($value['type'], ['crop', 'fit', 'origin', 'resize'], true) === false) {
                            $fail($attribute . '.type must be one of the following: crop, fit, origin, resize');
                        } else {

                            if ($value['type'] !== 'origin') {

                                if ((array_key_exists('resultWidth', $value) && is_int($value['resultWidth']) && $value['resultWidth'] > 0) === false) {
                                    $fail($attribute . '.resultWidth must be present and be a valid positive integer');
                                }

                                if ((array_key_exists('resultHeight', $value) && is_int($value['resultHeight']) && $value['resultHeight'] > 0) === false) {
                                    $fail($attribute . '.resultHeight must be present and be a valid positive integer');
                                }
                            }

                            $fullPath = ((array_key_exists('fullPath', $value) && is_string($value['fullPath'])) ? $value['fullPath'] : null);

                            if ($fullPath === null || $fullPath === '') {
                                $fail($attribute . '.fullPath must be present and be a non empty string');
                            }

                            if ($value['type'] !== 'origin') {

                                $width  = null;
                                $height = null;

                                if ((array_key_exists('width', $value) && ((is_int($value['width']) && $value['width'] >= 0) || $value['width'] === null)) === false) {
                                    $fail($attribute . '.width must be present and either be a valid integer or null');
                                } else {
                                    $width = (int)$value['width'];
                                }

                                if ((array_key_exists('height', $value) && ((is_int($value['height']) && $value['height'] >= 0) || $value['height'] === null)) === false) {
                                    $fail($attribute . '.height must be present and either be a valid integer or null');
                                } else {
                                    $height = (int)$value['height'];
                                }

                                if (strpos($fullPath, ($value['type'] . '_' . $width . '_' . $height)) === false) {
                                    $fail($attribute . '.fullPath must match the following format: {type}_{width}_{height}' . ((in_array($value['type'], ['fit', 'crop'], true) === true) ? '_{gravity}' : '') . '[_{watermark}]');
                                } else {

                                    $fullPathSegments       = explode('_', $fullPath);
                                    $fullPathSegmentCount   = count($fullPathSegments);

                                    if ($fullPathSegmentCount > 6 || preg_replace('/^' . $value['type'] . '_' . $width . '_' . $height . ((in_array($value['type'], ['fit', 'crop'], true) === true) ? '(_(center|north_west|north|north_east|west|east|south_west|south|south_east)|)' : '') . '(_watermark|)$/', '', $fullPath) !== '') {
                                        $fail($attribute . '.fullPath value "' . $fullPath . '" failed to match the following format: {type}_{width}_{height}' . ((in_array($value['type'], ['fit', 'crop'], true) === true) ? '_{gravity}' : '') . '[_watermark]');
                                    }
                                }

                            } elseif ($fullPath !== 'origin') {
                                $fail($attribute . '.fullPath must equal "origin" in case ' . $attribute . '.type equals "origin"');
                            }

                        }

                    }

                }
            ],
            '*.additionalData'              => 'required|array',
            '*.additionalData.imageInfo'    => [
                'required',
                'array',
                function ($attribute, $value, $fail) {

                    if ((array_key_exists('sourcePath', $value) && is_string($value['sourcePath']) && trim($value['sourcePath']) !== '') === false) {
                        $fail($attribute . '.sourcePath must be a non empty string');
                    } else {

                        if ((array_key_exists('entity', $value) && is_string($value['entity']) && trim($value['entity']) !== '' && array_key_exists('key', $value) && (is_string($value['key']) || is_numeric($value['key']))) === false) {
                            $fail($attribute . ' must contain valid entity details.');
                        } else {

                            try {
                                $entity = str_replace('\\\\', '\\', $value['entity'])::find($value['key']);
                            } catch (\Throwable $e) {
                                $entity = null;
                            }

                            if ($entity === null) {
                                $fail($attribute . ' has invalid entity details');
                            }

                        }

                    }

                }
            ],
            '*.additionalData.pipeList'      => 'required|array',
            '*.additionalData.pipeList.*'    => 'required|array',
            '*.additionalData.pipeList.*.pipe' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {

                    foreach ($value as $pipeItem) {
                        if (
                            (
                                is_array($pipeItem) &&
                                array_key_exists('persist', $pipeItem) && is_bool($pipeItem['persist']) &&
                                array_key_exists('action',
                                    $pipeItem) && is_string($pipeItem['action']) && in_array($pipeItem['action'],
                                    ['crop', 'fit', 'origin', 'resize'], true) === true &&
                                (
                                    $pipeItem['action'] === 'origin' ||
                                    (
                                        array_key_exists('width',
                                            $pipeItem) && is_int($pipeItem['width']) && $pipeItem['width'] >= 0 &&
                                        array_key_exists('height',
                                            $pipeItem) && is_int($pipeItem['height']) && $pipeItem['height'] >= 0 &&
                                        (
                                            array_key_exists('gravity', $pipeItem) === false ||
                                            (
                                                is_string($pipeItem['gravity']) &&
                                                in_array(
                                                    strtolower($pipeItem['gravity']),
                                                    ['center', 'north_west', 'north', 'north_east', 'west', 'east', 'south_west', 'south', 'south_east'],
                                                    true
                                                )
                                            )
                                        )
                                    )
                                )
                            ) === false
                        ) {
                            $fail($attribute . ' must be a valid element');
                        }
                    }

                }
            ],
        ];
    }
}