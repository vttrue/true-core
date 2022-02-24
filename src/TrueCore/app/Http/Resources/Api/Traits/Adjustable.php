<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 15.04.2019
 * Time: 12:10
 */

namespace TrueCore\App\Http\Resources\Api\Traits;

use Illuminate\Support\Facades\Storage;
use TrueCore\App\Helpers\ImageHelper;
use TrueCore\App\Libraries\Config;

/**
 * Trait Adjustable
 *
 * @package TrueCore\App\Http\Resources\Api\Traits
 */
trait Adjustable
{
    protected ?array    $fields                 = null;
    protected array     $thumbDimensions        = [[null, null]];
    protected array     $listThumbDimensions    = [[null, null]];
    protected array     $thumbType              = [['resize']];
    protected array     $listThumbType          = [['resize']];
    protected array     $thumbSettings          = [];

    /**
     * @return array
     */
    protected function getThumbSettings(): array
    {
        if (count($this->thumbSettings) === 0) {

            $baseThumbSettings = config('resize.sizeList', []);
            $baseThumbSettings = ((is_array($baseThumbSettings)) ? $baseThumbSettings : []);

            $thumbSettings       = Config::getInstance()->get('previewSizes', 'images', []);
            $thumbSettings       = ((is_array($thumbSettings)) ? $thumbSettings : []);

            $this->thumbSettings = array_merge($baseThumbSettings, $thumbSettings);
        }

//        dd($this->thumbSettings);

        return $this->thumbSettings;
    }

    /**
     * @param string $entity
     * @param int|null $width
     * @param int|null $height
     *
     * @return array|null
     */
    public function getThumbSetting(string $entity, ?int $width = null, ?int $height = null) : ?array
    {
        $thumbSizeSetting = $this->getThumbSettings();

        $allowedSizeList = ((array_key_exists($entity, $thumbSizeSetting) && is_array($thumbSizeSetting[$entity])) ? $thumbSizeSetting[$entity] : []);

        $thumbSetting = array_filter($allowedSizeList, function ($v) use ($width, $height) {
            $item = array_values($v);
            return ((((int)reset($item)['width'] === (int)$width)) && (((int)reset($item)['height'] === (int)$height)));
        });
        $thumbSetting = array_values($thumbSetting);
        return ((count($thumbSetting) > 0) ? reset($thumbSetting) : null);
    }

    /**
     * @param string $entity
     * @param int|null $width
     * @param int|null $height
     *
     * @return bool
     */
    protected function validateThumbDimensions(string $entity, $width, $height) : bool
    {
        return ($this->getThumbSetting($entity, $width, $height) !== null);
    }

    /**
     * @param string $imageField
     *
     * @return string
     */
    protected function getThumbType(string $imageField) : string
    {
        $thumbType = ((array_key_exists($imageField, $this->thumbType) && in_array($this->thumbType[$imageField], ['resize', 'fit'], true)) ? $this->thumbType[$imageField] : null);

        if ($thumbType === null) {
            $thumbType = ((array_key_exists(0, $this->thumbType) && in_array($this->thumbType[0], ['resize', 'fit']) === true) ? $this->thumbType[0] : 'fit');
        }

        return $thumbType;
    }

    /**
     * @param string $imageField
     *
     * @return int|null
     */
    protected function getThumbWidth(string $imageField) : ?int
    {
        if (array_key_exists($imageField, $this->thumbDimensions) && is_array($this->thumbDimensions[$imageField]) && count($this->thumbDimensions[$imageField]) === 2) {
            return $this->thumbDimensions[$imageField][0];
        }

        return ((array_key_exists(0, $this->thumbDimensions) && is_array($this->thumbDimensions[0]) && count($this->thumbDimensions[0]) === 2) ? $this->thumbDimensions[0][0] : null);
    }

    /**
     * @param string $imageField
     *
     * @return int|null
     */
    protected function getThumbHeight(string $imageField) : ?int
    {
        if (array_key_exists($imageField, $this->thumbDimensions) && is_array($this->thumbDimensions[$imageField]) && count($this->thumbDimensions[$imageField]) === 2) {
            return $this->thumbDimensions[$imageField][1];
        }

        return ((array_key_exists(0, $this->thumbDimensions) && is_array($this->thumbDimensions[0]) && count($this->thumbDimensions[0]) === 2) ? $this->thumbDimensions[0][1] : null);
    }

    /**
     * @param string $imageField
     *
     * @return string
     */
    protected function getListThumbType(string $imageField) : string
    {
        $thumbType = ((array_key_exists($imageField, $this->listThumbType) && in_array($this->listThumbType[$imageField], ['resize', 'fit'], true)) ? $this->listThumbType[$imageField] : null);

        if ($thumbType === null) {
            $thumbType = ((array_key_exists(0, $this->listThumbType) && in_array($this->listThumbType[0], ['resize', 'fit']) === true) ? $this->listThumbType[0] : 'resize');
        }

        return $thumbType;
    }

    /**
     * @param string $imageField
     *
     * @return int|null
     */
    protected function getListThumbWidth(string $imageField) : ?int
    {
        if (array_key_exists($imageField, $this->listThumbDimensions) && is_array($this->listThumbDimensions[$imageField]) && count($this->listThumbDimensions[$imageField]) === 2) {
            return $this->listThumbDimensions[$imageField][0];
        }

        return ((array_key_exists(0, $this->listThumbDimensions) && is_array($this->listThumbDimensions[0]) && count($this->listThumbDimensions[0]) === 2) ? $this->listThumbDimensions[0][0] : null);
    }

    /**
     * @param string $imageField
     *
     * @return int|null
     */
    protected function getListThumbHeight(string $imageField) : ?int
    {
        if (array_key_exists($imageField, $this->listThumbDimensions) && is_array($this->listThumbDimensions[$imageField]) && count($this->listThumbDimensions[$imageField]) === 2) {
            return $this->listThumbDimensions[$imageField][1];
        }

        return ((array_key_exists(0, $this->listThumbDimensions) && is_array($this->listThumbDimensions[0]) && count($this->listThumbDimensions[0]) === 2) ? $this->listThumbDimensions[0][1] : null);
    }

    /**
     * @param string $fieldName
     * @param array $fieldValue
     * @param string $gravity
     * @param bool $returnOriginalIfNoneFound
     *
     * @return array
     * @throws \Exception
     */
    protected function getThumb(string $fieldName, array $fieldValue, string $gravity = 'center', bool $returnOriginalIfNoneFound = false) : array
    {
        return ImageHelper::getImageField($fieldValue, $this->getThumbType($fieldName), $this->getThumbWidth($fieldName), $this->getThumbHeight($fieldName), $gravity, $returnOriginalIfNoneFound);
    }

    /**
     * @param string $fieldName
     * @param array $fieldValue
     * @param string $gravity
     * @param bool $returnOriginalIfNoneFound
     *
     * @return array
     * @throws \Exception
     */
    protected function getListThumb(string $fieldName, array $fieldValue, string $gravity = 'center', bool $returnOriginalIfNoneFound = false) : array
    {
        return ImageHelper::getImageField($fieldValue, $this->getListThumbType($fieldName), $this->getListThumbWidth($fieldName), $this->getListThumbHeight($fieldName), $gravity, $returnOriginalIfNoneFound);
    }

    /**
     * @param array $thumbDimensions
     *
     * @return $this
     */
    public function applyThumbDimensions(array $thumbDimensions) : self
    {
        return $this->thumbDimensionApplier($thumbDimensions, 'thumbDimensions');
    }

    /**
     * @param array $thumbDimensions
     *
     * @return $this
     */
    public function applyListThumbDimensions(array $thumbDimensions) : self
    {
        return $this->thumbDimensionApplier($thumbDimensions, 'listThumbDimensions');
    }

    /**
     * @param array $thumbDimensions
     * @param string $type
     *
     * @return $this
     */
    private function thumbDimensionApplier(array $thumbDimensions, string $type) : self
    {
        try {

            if (in_array($type, ['thumbDimensions', 'listThumbDimensions'], true) === false) {
                throw new \Exception('Unknown thumb dimension type');
            }

            foreach ($thumbDimensions AS $imageField => $thumbDimension) {

                if ((is_string($thumbDimension) === true && trim($thumbDimension) !== '') || (is_array($thumbDimension) && count($thumbDimension) === 2)) {

                    [$width, $height] = ((is_string($thumbDimension) === true) ? explode(',', $thumbDimension) : $thumbDimension);

                    $width  = ((is_numeric($width) && $width > 0) ? (int)$width : null);
                    $height = ((is_numeric($height) && $height > 0) ? (int)$height : null);

                    if ($this->validateThumbDimensions(((property_exists($this, 'thumbEntity')) ? $this->thumbEntity : ''), $width, $height)) {
                        $this->{$type}[$imageField] = [$width, $height];
                    }

                }

            }

        } catch (\Exception $e) {
            //
        }

        return $this;
    }

    /**
     * @param array $thumbType
     *
     * @return $this
     */
    public function applyThumbType(array $thumbType) : self
    {
        $this->thumbType = array_filter($thumbType, static fn ($v) : bool => (in_array($v, ['fit', 'resize'], true)));

        return $this;
    }

    /**
     * @param array $thumbType
     *
     * @return $this
     */
    public function applyListThumbType(array $thumbType) : self
    {
        $this->listThumbType = array_filter($thumbType, static fn ($v) : bool => (in_array($v, ['fit', 'resize'], true)));

        return $this;
    }

    /**
     * @param array|null $fields
     *
     * @return $this
     */
    public function applyFieldList(?array $fields) : self
    {
        $this->fields = $fields;

        return $this;
    }
}
