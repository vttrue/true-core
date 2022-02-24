<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 02.09.2019
 * Time: 14:55
 */

namespace TrueCore\App\Models\Traits;

/**
 * Trait HasDescriptions
 *
 * @package TrueCore\App\Models\Traits
 */
trait HasDescriptions
{
    /**
     * @return string
     */
    private function getDescriptionEntityClassName() : string
    {
        return static::class . 'Description';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function description()
    {
        return $this->hasOne($this->getDescriptionEntityClassName(), $this->getForeignKey(), $this->getKeyName());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descriptions()
    {
        return $this->hasMany($this->getDescriptionEntityClassName(), $this->getForeignKey(), $this->getKeyName());
    }
}