<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 05.07.2019
 * Time: 19:58
 */

namespace TrueCore\App\Models\Traits;

use TrueCore\App\Helpers\CommonHelper;
use TrueCore\App\Jobs\GenerateThumbs;
use TrueCore\App\Libraries\{Config, ImageResizeManager\Exceptions\ApiResponseException, ImageResizeManager\ImageResizeManager};
use TrueCore\App\Models\System\ImagePreview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Trait HasImageFields
 *
 * @property array $imageFields
 * @property string $masterImageEntityName
 * @property array $imageEntityRelations
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|ImagePreview[] $thumbs
 *
 * @package TrueCore\App\Models\Traits
 */
trait HasImageFields
{
    /**
     * @return array
     */
    public function getImageFields(): array
    {
        return ((property_exists($this, 'imageFields') && is_array($this->imageFields)) ? $this->imageFields : ['image', 'og_image', 'file_path']);
    }

    /**
     * @return string
     */
    public function getMasterImageEntityName(): string
    {
        return ((property_exists($this, 'masterImageEntityName') && is_string($this->masterImageEntityName)) ? $this->masterImageEntityName : self::class);
    }

    /**
     * @return array
     */
    public function getImageEntityRelations(): array
    {
        return ((property_exists($this, 'imageEntityRelations') && is_array($this->imageEntityRelations)) ? $this->imageEntityRelations : []);
    }

    public static function bootHasImageFields()
    {
        if (method_exists(self::class, 'saved')) {

            self::saved(function (Model $model) {

                // @TODO: refactor. This logic must be inside the Service Image trait | Deprecator @ 2020-03-13
                // Checking if this is not an *Image model, otherwise we must only react to new models
                // @TODO: inspect later | Deprecator @ 2020-12-21
                //if (strpos(get_class($model), 'Image') === false || $model->wasRecentlyCreated === true) {

                $imageFields = $model->getImageFields();

                if (count($imageFields) > 0) {

                    $nestedImageFields = array_filter(
                        $imageFields,
                        static fn(string $field) : bool => (strpos($field, '.') !== false)
                    );

                    $baseNestedImageFields = array_unique(
                        array_map(
                            static fn(string $field) : string => substr($field, 0, strpos($field, '.')),
                            $nestedImageFields
                        )
                    );

                    $changes = $model->getChanges();
                    $changes = array_filter($changes, static function (string $k) use ($imageFields, $baseNestedImageFields) : bool {
                        return (in_array($k, $imageFields, true) || in_array($k, $baseNestedImageFields, true));
                    }, ARRAY_FILTER_USE_KEY);
                    //dump([$model->getChanges(), $model->getDirty(), $model->getOriginal()]);

                    $changeCount = count($changes);

                    if ($model->wasRecentlyCreated === true || $changeCount > 0) {

                        if ($model->wasRecentlyCreated === false) {

                            $removedImageList = array_filter($model->getOriginal(), function ($v, string $k) use ($changes) : bool {
                                return (is_string($v) && trim($v) !== '' && array_key_exists($k, $changes) && $changes[$k] !== $v);
                            }, ARRAY_FILTER_USE_BOTH);

                            if (count($removedImageList) > 0) {
                                foreach ($model->thumbs AS $thumbModel) {
                                    /** @var ImagePreview $thumbModel */

                                    if (in_array($thumbModel->image_path, $removedImageList, true) === true) {
                                        $thumbModel->delete();
                                    }
                                }
                            }

                        }

                        $model->generateThumbs(true);
                    }
                }
                //}

            });

        }

        if (method_exists(self::class, 'deleted')) {

            self::deleted(function (Model $model) {

                if (method_exists($model, 'getImageFields')) {
                    $imageFields = $model->getImageFields();

                    foreach ($imageFields AS $imageField) {
                        if (is_string($model->{$imageField}) && trim($model->{$imageField}) !== '' && Storage::disk('image')->exists($model->{$imageField}) === true) {
                            Storage::disk('image')->delete($model->{$imageField});
                        }
                    }

                    foreach ($model->thumbs AS $thumbModel) {
                        /** @var ImagePreview $thumbModel */
                        $thumbModel->delete();
                    }
                }

            });

        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function thumbs()
    {
        return $this->hasMany(ImagePreview::class, 'entity_id', 'id')->where('entity_namespace', '=', static::class);
    }

    public function clearThumbs(): void
    {
        $classNameParts = explode('\\', get_class($this));
        $classNameParts = array_splice($classNameParts, (count($classNameParts) - 2));
        $imageDir       = strtolower(implode('_', $classNameParts));

        if (Storage::disk('image')->exists('cache/' . $imageDir . '/' . $this->getKey())) {
            Storage::disk('image')->deleteDirectory('cache/' . $imageDir . '/' . $this->getKey());
        }
    }

    /**
     * @param array $image
     * @param array $thumbList
     */
    public function saveThumbs(array $image, array $thumbList) : void
    {
        if (count($thumbList) > 0) {

            $previewList = [];

            $thumbEntry = $this->thumbs()
                ->where('entity_namespace', '=', static::class)
                ->where('entity_id', '=', $this->getKey())
                ->where('image_path', '=', $image['path'])
                ->first();

            $existingPreviewList = [];

            if ($thumbEntry !== null) {
                $previewList[$image['path']] = ((is_array($thumbEntry->preview_list) === true) ? $thumbEntry->preview_list : []);

                // @TODO: refactor later | Deprecator @ 2020-12-24
                $existingPreviewList         = array_map(static function (array $previewItem) : string {

                    if (array_key_exists('previewPath', $previewItem) === true) {
                        unset($previewItem['previewPath']);
                    }

                    if (array_key_exists('params', $previewItem) === true) {
                        unset($previewItem['params']);
                    }

                    if (array_key_exists('realWidth', $previewItem) === true) {
                        unset($previewItem['realWidth']);
                    }

                    if (array_key_exists('realHeight', $previewItem) === true) {
                        unset($previewItem['realHeight']);
                    }

                    ksort($previewItem);

                    return json_encode($previewItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }, $previewList[$image['path']]);
            }

            foreach ($thumbList AS $thumbItem) {

                [
                    'path'       => $previewPath,
                    'method'     => $method,
                    'gravity'    => $gravity,
                    'width'      => $width,
                    'height'     => $height,
                    'realWidth'  => $realWidth,
                    'realHeight' => $realHeight
                ] = $thumbItem;

                if ($previewPath !== $image['path']) {

                    // @TODO: make unique | Deprecator @ 2020-12-15

                    $additionalData            = ((array_key_exists('additionalData', $image) && is_array($image['additionalData'])) ? $image['additionalData'] : []);
                    $additionalData['gravity'] = $gravity;

                    if (array_key_exists($image['path'], $previewList) === false) {
                        $previewList[$image['path']] = [];
                    }

                    $previewItem = [
                        'height' => $height,
                        'type'   => $method,
                        'width'  => $width,
                    ];

                    $previewItemStr = json_encode($previewItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                    $previewItem = [
                        'height'        => $height,
                        'params'        => $additionalData,
                        'previewPath'   => $previewPath,
                        'realHeight'    => $realHeight,
                        'realWidth'     => $realWidth,
                        'type'          => $method,
                        'width'         => $width,
                    ];

                    $existingPreviewIndex = array_search($previewItemStr, $existingPreviewList, true);

                    if ($existingPreviewIndex !== false) {
                        $previewList[$image['path']][$existingPreviewIndex] = $previewItem;
                    } else {
                        $previewList[$image['path']][] = $previewItem;
                    }

                }

            }

            foreach ($previewList AS $imagePath => $previewEntryList) {

                if ($thumbEntry === null) {
                    $thumbEntry = $this->thumbs()->newModelInstance();
                }

                $thumbEntry->fill([
                    'entity_namespace' => static::class,
                    'entity_id'        => $this->getKey(),
                    'image_path'       => $imagePath,
                    'preview_list'     => $previewEntryList
                ])->save();
            }
        }
    }

    /**
     * @param bool $viaQueue
     * @param bool $force
     * @param string $entityClass
     *
     * @return array
     * @throws ApiResponseException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function generateThumbs(bool $viaQueue = true, bool $force = false, string $entityClass = self::class): array
    {
        $waterMarkSettings = Config::getInstance()->get('watermark', 'watermark', null);
        $waterMarkSettings = ((is_array($waterMarkSettings)) ? $waterMarkSettings : null);

        if (is_array($waterMarkSettings) && array_key_exists('image', $waterMarkSettings) && is_array($waterMarkSettings['image']) && array_key_exists('image', $waterMarkSettings['image']) && is_string($waterMarkSettings['image']['image'])) {
            $waterMarkSettings['image'] = $waterMarkSettings['image']['image'];
        }

        $thumbList = [];

        if ($viaQueue === true) {
            GenerateThumbs::dispatch($this, time(), $force);
            return $thumbList;
        }

        $entityRuleList = Config::getInstance()->get('previewSizes', 'images', []);
        $entityRuleList = ((is_array($entityRuleList)) ? $entityRuleList : []);
        $entityRuleList = array_filter($entityRuleList, function ($v) {
            return (count($v) > 0);
        });

        // @TODO: Refactor later to only rely on Service classes | Deprecator @ 2020-12-21
        $entityModelRuleList = [];

        foreach ($entityRuleList AS $serviceClassName => $entityRule) {

            try {

                /** @var \TrueCore\App\Services\Interfaces\Service $serviceInstance */
                $serviceInstance = new $serviceClassName;

                $entityRule = array_map(function ($v) {
                    $item = array_values($v);
                    return reset($item);
                }, $entityRule);
                $entityModelRuleList[get_class($serviceInstance->getRepository()->getModel())] = $entityRule;

            } catch (\Throwable $e) {
                //
            }

        }

        $entityThumbList = [];

        $fullClassName = $entityClass;

        $classParents = class_parents($fullClassName);

        if (array_key_exists($fullClassName, $entityModelRuleList) === false) {

            if (strpos(get_class($this), 'Image') !== false) {
                $fullClassName = str_replace('Image', '', $fullClassName);

                if (array_key_exists($fullClassName, $entityModelRuleList) && is_array($entityModelRuleList[$fullClassName])) {
                    $entityThumbList = $entityModelRuleList[$fullClassName];
                }

            } else {

                foreach ($classParents AS $classParentName) {
                    if (array_key_exists($classParentName, $entityModelRuleList) && is_array($entityModelRuleList[$classParentName])) {
                        $entityThumbList = $entityModelRuleList[$classParentName];
                    }
                }

            }

            if (count($entityThumbList) === 0 && array_key_exists($this->getMasterImageEntityName(), $entityModelRuleList) && is_array($entityModelRuleList[$this->getMasterImageEntityName()])) {
                $entityThumbList = $entityModelRuleList[$this->getMasterImageEntityName()];
            }

        } elseif (is_array($entityModelRuleList[$fullClassName]) === true) {
            $entityThumbList = $entityModelRuleList[$fullClassName];
        }
        $entityThumbList = array_merge(array_map(function ($v) {
            return ['method' => 'resize'] + $v;
        }, $entityThumbList), array_map(function ($v) {
            return ['method' => 'fit'] + $v;
        }, $entityThumbList));

        $preDefinedRuleList = config('resize.sizeList', []);
        $preDefinedRuleList = ((is_array($preDefinedRuleList) === true) ? $preDefinedRuleList : []);

        if (array_key_exists($fullClassName, $preDefinedRuleList) && is_array($preDefinedRuleList[$fullClassName])) {
            $entityThumbList = array_merge($entityThumbList, $preDefinedRuleList[$fullClassName]);
        }

        $sizeList = [];

        foreach ($this->getImageFields() AS $imageField) {

            $imageFieldValue = $this->getAttribute(((($dotPos = strpos($imageField, '.')) !== false) ? substr($imageField, 0, $dotPos) : $imageField));

            if (is_array($imageFieldValue) === true) {
                $imageFieldValue = CommonHelper::fieldExtractor($imageFieldValue, substr($imageField, ($dotPos + 1)));
            }

            if (is_string($imageFieldValue) && trim($imageFieldValue) !== '' && (strrpos($imageFieldValue, '.svg') !== (strlen($imageFieldValue) - 4)) && Storage::disk('image')->exists($imageFieldValue) === true) {

                foreach ($entityThumbList AS $thumbRule) {

                    $width  = ((array_key_exists('width', $thumbRule) && is_numeric($thumbRule['width']) && (int)$thumbRule['width'] > 0) ? (int)$thumbRule['width'] : null);
                    $height = ((array_key_exists('height', $thumbRule) && is_numeric($thumbRule['height']) && (int)$thumbRule['height'] > 0) ? (int)$thumbRule['height'] : null);

                    $shouldHaveWaterMark = (array_key_exists('watermark', $thumbRule) && is_bool($thumbRule['watermark']) && $thumbRule['watermark'] === true);

                    if (array_key_exists($imageFieldValue, $sizeList) === false) {
                        $sizeList[$imageFieldValue] = [
                            'path'           => $imageFieldValue,
                            'additionalData' => [
                                'sourcePath' => $imageFieldValue,
                                'entity'     => static::class,
                                'key'        => $this->getKey()
                            ],
                            'sizeList'       => []
                        ];
                    }

                    // @TODO: Implement watermark | Deprecator @ 2020-12-09

                    $sizeItem = [
                        'method'  => $thumbRule['method'],
                        'width'   => $width,
                        'height'  => $height,
                        'gravity' => ((array_key_exists('gravity', $thumbRule) && is_string($thumbRule['gravity'])) ? $thumbRule['gravity'] : 'center')
                    ];

                    if (array_key_exists('backgroundColor', $thumbRule) === true) {
                        $sizeItem['backgroundColor'] = $thumbRule['backgroundColor'];
                    }

                    if (array_key_exists('saveAspect', $thumbRule) && is_bool($thumbRule['saveAspect'])) {
                        $sizeItem['saveAspect'] = $thumbRule['saveAspect'];
                    }

                    $sizeList[$imageFieldValue]['sizeList'][] = $sizeItem;

                    if ($width !== null || $height !== null) {

                        $retinaWidth  = (($width !== null) ? ($width * 2) : null);
                        $retinaHeight = (($height !== null) ? ($height * 2) : null);

                        $sizeItem = [
                                'width'  => $retinaWidth,
                                'height' => $retinaHeight
                            ] + $sizeItem;

                        $sizeList[$imageFieldValue]['sizeList'][] = $sizeItem;
                    }
                }
            }
        }

        if (count($sizeList) > 0) {

            foreach ($sizeList AS $imagePath => $imageInfo) {

                //dd(\Illuminate\Support\Facades\Storage::disk('image')->path($imagePath), \Illuminate\Support\Facades\Storage::disk('image')->exists($imagePath), config('resize.driver'));

                try {

                    $currentThumbList = $this->generateThumbList([
                        'path'           => $imagePath,
                        'additionalData' => $imageInfo['additionalData'],
                        'force'          => $force
                    ], $imageInfo['sizeList']);

                } catch (ApiResponseException $e) {

                    // Common error status codes that signal us it's worth going for another iteration
                    if (in_array($e->getStatusCode(), [100, 500, 502, 503, 504], true) === false) {
                        throw new ApiResponseException($e->getMessage(), $e->getCode(), $e, $e->getStatusCode());
                    }

                    return $this->generateThumbs($viaQueue, $force);

                }

                $thumbList[] = $currentThumbList;

                $this->saveThumbs($currentThumbList['image'], $currentThumbList['thumbList']);
            }
        }

        $traits    = class_uses($this);
        $hasImages = in_array(HasImages::class, $traits);

        if ($hasImages) {

            $recordImages = $this->images;

            foreach ($recordImages AS $image) {
                if (method_exists($image, 'generateThumbs')) {
                    $thumbList = [...$thumbList, ...$image->generateThumbs(false, $force, $fullClassName)];
                }
            }
        }

        $relatedImageEntityRelations = $this->getImageEntityRelations();

        foreach ($relatedImageEntityRelations AS $relatedImageEntityClassName => $relatedImageEntityRelationName) {
            if (method_exists($this, $relatedImageEntityRelationName)) {
                $this->{$relatedImageEntityRelationName}->each(function ($relatedImageEntity) use ($fullClassName, &$thumbList, $force) {
                    if (method_exists($relatedImageEntity, 'generateThumbs') === true) {
                        $thumbList = [...$thumbList, ...$relatedImageEntity->generateThumbs(false, $force, $fullClassName)];
                    }
                });
            }
        }

        return $thumbList;
    }

    /**
     * @param array $image
     * @param array $sizeList
     *
     * @return array
     * @throws ApiResponseException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function generateThumbList(array $image, array $sizeList = []) : array
    {
        //dd($image, $sizeList);
        $driver = config('resize.driver');

        $imagePath = Storage::disk('image')->path($image['path']);

        $driver = (($driver === 'trueResizer' && strpos($imagePath, '/') === 0) ? 'local' : $driver);

        if ($driver === 'trueResizer') {
            $image['path'] = $imagePath;
        }

//        $image['additionalData']['mayBeRemoved'] = ($driver === 'local');
        $image['additionalData']['mayBeRemoved'] = true;

        $result = (new ImageResizeManager($driver))->processImage($image, $sizeList);

        // @TODO: temporary approach, refactor later to handle Exceptions | Deprecator @ 2020-12-14
        if (array_key_exists('image', $result) && is_array($result['image']) && array_key_exists('additionalData', $result['image']) && is_array($result['image']['additionalData']) && array_key_exists('sourcePath', $result['image']['additionalData'])) {
            $result['image']['path'] = $result['image']['additionalData']['sourcePath'];
        }

        return $result;
    }
}
