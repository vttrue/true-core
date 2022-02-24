<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 05.07.2019
 * Time: 19:57
 */

namespace TrueCore\App\Models\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasImages
 *
 * @package TrueCore\App\Models\Traits
 */
trait HasImages
{
    public static function bootHasImages()
    {
        self::saved(function (Model $model) {

//            $changes = $model->getChanges();
//            $changes = array_filter($changes, function($k) use($model) {
//                return ($k !== 'updated_at');
//            }, ARRAY_FILTER_USE_KEY);
//
//            if(method_exists($model, 'generateThumbs')) {
//                $model->generateThumbs();
//            }

        });
    }

    /**
     * @return string
     */
    private function getImageEntityClassName() : string
    {
        return static::class . 'Image';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function firstImage()
    {
        return $this->hasOne($this->getImageEntityClassName(), $this->getForeignKey(), $this->getKeyName())->oldest('sort_order');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany($this->getImageEntityClassName(), $this->getForeignKey(), $this->getKeyName())->oldest('sort_order');
    }
}