<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 14.01.2020
 * Time: 22:10
 */

namespace TrueCore\App\Services\Traits\Repository\Eloquent;

use Illuminate\Database\Eloquent\Model;
use TrueCore\App\Models\Traits\{
    HasImageFields as ModelHasImageFields,
    HasImages as ModelHasImages
};
use Illuminate\Support\Str;

/**
 * Trait HasImages
 *
 * @property array $imageFields
 *
 * @package TrueCore\App\Services\Traits\Repository\Eloquent
 */
trait HasImages
{
    /**
     * @return array
     */
    protected function getImageFields() : array
    {
        return ((property_exists($this, 'imageFields')) ? $this->imageFields : []);
    }

    /**
     * @param Model $model
     * @param array $imageList
     *
     * @throws \Exception
     */
    protected function attachImages(Model $model, array $imageList) : void
    {
        //dump([$imageList, 'imageList']);
        $fullClassName  = get_class($model);
        $classUses      = class_uses($model);

        if(in_array(ModelHasImages::class, $classUses) === false) {
            throw new \Exception('Model ' . $fullClassName . ' does not have any Images');
        }

        $imageFields = $this->getImageFields();

        if(count($imageFields) === 0) {
            throw new \Exception('$imageFields property of class ' . static::class . ' must be set correctly');
        }

        $imageList      = array_filter($imageList, function ($image) {
            return is_array($image);
        });
        $imageIdList    = array_filter(array_column($imageList, 'id'), function($v) {
            return is_numeric($v);
        });

        $imagesToDeleteQuery = $model->images();

        if(count($imageIdList) > 0) {
            $imagesToDeleteQuery->whereNotIn('id', $imageIdList);
        }

        $imagesToDelete = $imagesToDeleteQuery->get();

        foreach($imagesToDelete AS $imageToDelete) {
            $this->deleteModel($imageToDelete);
        }

        if (count($imageList) > 0) {

            foreach ($imageList AS $k => $image) {

                $sortOrder = ((array_key_exists('sortOrder', $image) && is_numeric($image['sortOrder']) && (int)$image['sortOrder'] >= 0) ? (int)$image['sortOrder'] : $k);

                if (!array_key_exists('id', $image) || (int)$image['id'] === 0) {

                    $imageEntity = $model->images()->newModelInstance([
                        $model->getForeignKey()       => $model->id
                    ])->fill([
                        $model->getForeignKey()       => $model->id,
                        'sort_order'                  => $sortOrder,
                    ]);

                } else {
                    /** @var Model|null $imageEntity */
                    $imageEntity = $model->images()->find($image['id']);

                    if ($imageEntity !== null && (int)$imageEntity->sort_order !== $sortOrder) {
                        $imageEntity->sort_order    = $k;
                    } else if($imageEntity === null) {
                        $imageEntity = $model->images()->newModelInstance([
                            $model->getForeignKey()       => $model->id
                        ])->fill([
                            $model->getForeignKey()       => $model->id,
                            'sort_order'                  => $sortOrder,
                        ]);
                    }
                }

                $imageEntity->save();

                $this->saveImages($imageEntity, $image);

            }
        }
    }

    /**
     * @param Model $model
     * @param array $imageFields
     *
     * @throws \Exception
     */
    protected function saveImages(Model $model, array $imageFields) : void
    {
        $fullClassName  = get_class($model);
        $classUses      = class_uses($model);

        if(in_array(ModelHasImageFields::class, $classUses) === false) {
            throw new \Exception('Model ' . $fullClassName . ' does not have any Image Fields defined');
        }

        $entityImageFields = $this->getImageFields();

        if(count($entityImageFields) === 0) {
            throw new \Exception('$imageFields property of class ' . static::class . ' must be set correctly');
        }

        $classNames     = class_parents($model);
        array_unshift($classNames, $fullClassName);

        $entityFields = [];

        foreach($classNames AS $className) {
            if(array_key_exists($className, $entityImageFields) && is_array($entityImageFields[$className])) {
                $entityFields = $entityImageFields[$className];
                break;
            }
        }

        if(count($entityFields) === 0) {
            throw new \Exception('An Image entity must have at least one field');
        }

        $fields = array_map(function ($field) use($imageFields) {
            return (($field instanceof \Closure) ? $field($imageFields) : $imageFields[$field]);
        }, array_filter($entityFields, function ($value, $field) use($imageFields) {
            return ((is_string($value) && array_key_exists($value, $imageFields)) || (array_key_exists(Str::camel($field), $imageFields) && ($value instanceof \Closure)));
        }, ARRAY_FILTER_USE_BOTH));
        //dd($fullClassName, $classUses, $imageFields, $entityImageFields, $entityFields, $fields);
        $shouldSave = false;

        foreach($fields AS $field => $fieldValue) {
//dump([$model->id, $model->{$field}, $fieldValue]);

            if (is_string($fieldValue) || $fieldValue === null) {
                $fieldValue = [
                    'image' => $fieldValue
                ];
            }

            if (is_array($fieldValue) || $fieldValue === null) {

                if ((is_array($fieldValue) && array_key_exists('image', $fieldValue) && (((is_string($fieldValue['image']) && $fieldValue['image'] !== '') || (is_array($fieldValue['image']) && array_key_exists('image', $fieldValue['image']) && is_string($fieldValue['image']['image']) && $fieldValue['image']['image'] !== '')) || $fieldValue['image'] === null)) === false) {
                    continue;
                }

                //            dd([$fieldValue, $model->{$field}, $model->id]);
                $imagePath = ((is_array($fieldValue['image'])) ? $fieldValue['image']['image'] : $fieldValue['image']);

                if (is_array($fieldValue['image']) && array_key_exists('width', $fieldValue['image']) && array_key_exists('height', $fieldValue['image'])) {
                    $imageWidth = ((is_numeric($fieldValue['image']['width']) && (int)$fieldValue['image']['width'] > 0) ? (int)$fieldValue['image']['width'] : null);
                    $imageHeight = ((is_numeric($fieldValue['image']['height']) && (int)$fieldValue['image']['height'] > 0) ? (int)$fieldValue['image']['height'] : null);
//                  $imageWidth = ((array_key_exists('width', $fieldValue) && is_numeric($fieldValue['width']) && (int)$fieldValue['width'] > 0) ? (int)$fieldValue['width'] : null);
//                  $imageHeight = ((array_key_exists('height', $fieldValue) && is_numeric($fieldValue['height']) && (int)$fieldValue['height'] > 0) ? (int)$fieldValue['height'] : null);
                } else {
                    $imageWidth     = false;
                    $imageHeight    = false;
                }

                if(($model->{$field} !== $imagePath && $imageWidth !== false && $imageHeight !== false) || ($imagePath === null)) {

                    $model->{$field}                                                    = $imagePath;
                    $model->{(($field !== 'file_path') ? $field . '_' : '') . 'width'}  = (($imageWidth !== false) ? $imageWidth : null);
                    $model->{(($field !== 'file_path') ? $field . '_' : '') . 'height'} = (($imageHeight !== false) ? $imageHeight : null);

                    $shouldSave = true;
                }

            }

        }
//dd($fields,$shouldSave);
        if($shouldSave === true) {
            $this->saveModel($model);
        }
        //print_r([$model, $shouldSave]);die;
    }

    /**
     * @param Model $model
     * @param string $field
     *
     * @return array
     */
    protected function getImageField(Model $model, string $field) : array
    {
        $widthField     = (($field !== 'file_path') ? $field . '_' : '') . 'width';
        $heightField    = (($field !== 'file_path') ? $field . '_' : '') . 'height';

        return [
            'image'     => $model->{$field},
            'width'     => (($model->{$widthField} !== null) ? (int)$model->{$widthField} : null),
            'height'    => (($model->{$heightField} !== null) ? (int)$model->{$heightField} : null),
            'thumbs'    => (($model->{$field} !== null) ? $this->getThumbList($model, $model->{$field}) : [])
        ];
    }

    /**
     * @param Model $model
     * @param string $imagePath
     * @return array
     */
    protected function getThumbList(Model $model, string $imagePath) : array
    {
        // @TODO: Throw an Exception | Deprecator @ 2020-01-22
        if($model->thumbs === null) {
            return [];
        }

        $thumbList = $model->thumbs->toArray();

        $resultThumbList = [];

        foreach ($thumbList as $thumb) {

            if ( $thumb['image_path'] !== $imagePath ) {
                continue;
            }

            $previewList = ((is_array($thumb['preview_list']) === true) ? $thumb['preview_list'] : []);

            foreach ($previewList as $previewEntry) {

                $thumbKey = (($previewEntry['width'] !== null) ? $previewEntry['width'] : 'auto') . 'x' . (($previewEntry['height'] !== null) ? $previewEntry['height'] : 'auto');

                if ( array_key_exists($previewEntry['type'], $resultThumbList) ) {
                    $resultThumbList[$previewEntry['type']][$thumbKey] = ['createdAt' => $thumb['created_at'], 'updatedAt' => $thumb['updated_at']] + $previewEntry;
                } else {
                    $resultThumbList[$previewEntry['type']] = [
                        $thumbKey => ['createdAt' => $thumb['created_at'], 'updatedAt' => $thumb['updated_at']] + $previewEntry,
                    ];
                }
            }

        }

        return array_reduce(array_map(function($v) use ($resultThumbList) {

            uasort($v, function($a, $b) {
                return (((is_int($a['width'])) ? $a['width'] : 0) + ((is_int($a['height'])) ? $a['height'] : 0)) <=> (((is_int($b['width'])) ? $b['width'] : 0) + ((is_int($b['height'])) ? $b['height'] : 0));
            });

            return array_map(function($thumb) use($v) {

                $key2x = ((is_int($thumb['width'])) ? ($thumb['width'] * 2) : 'auto') . 'x' . ((is_int($thumb['height'])) ? ($thumb['height'] * 2) : 'auto');

                $thumb2x      = $thumb['previewPath'];
                $realWidth2x  = $thumb['realWidth'];
                $realHeight2x = $thumb['realHeight'];

                if ( array_key_exists($key2x, $v) === true ) {
                    $thumb2x      = $v[$key2x]['previewPath'];
                    $realWidth2x  = $v[$key2x]['realWidth'];
                    $realHeight2x = $v[$key2x]['realHeight'];
                }

                return [
                    'type'         => $thumb['type'],
                    'thumb'        => $thumb['previewPath'],
                    'thumb2x'      => $thumb2x,
                    'width'        => $thumb['width'],
                    'height'       => $thumb['height'],
                    'realWidth'    => $thumb['realWidth'],
                    'realHeight'   => $thumb['realHeight'],
                    'realWidth2x'  => $realWidth2x,
                    'realHeight2x' => $realHeight2x,
                    'params'       => ((array_key_exists('params', $thumb) && is_array($thumb['params'])) ? $thumb['params'] : []),
                    'createdAt'    => $thumb['createdAt'],
                    'updatedAt'    => $thumb['updatedAt']
                ];
            }, $v);

        }, $resultThumbList), function($accumulator, $currentValue) {
            return array_merge(array_values($accumulator), array_values($currentValue));
        }, []);
    }
}
