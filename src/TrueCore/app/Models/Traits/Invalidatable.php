<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 14.01.2019
 * Time: 13:23
 */

namespace TrueCore\App\Models\Traits;

use \TrueCore\App\Libraries\Cache;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait Invalidatable
 *
 * @package TrueCore\App\Models\Traits
 */
trait Invalidatable
{
    public static function bootInvalidatable()
    {
        if(method_exists(static::class, 'saved')) {

            static::saved(function(Model $model) {

                $className = basename(str_replace('\\', '/', get_class($model)));

                if(method_exists($model, 'saved' . $className)) {
                    $model->{'saved' . $className}();
                }

            });
        }

        if(method_exists(static::class, 'deleting')) {
            static::deleting(function(Model $model) {

                $className = basename(str_replace('\\', '/', get_class($model)));

                if(method_exists($model, 'deleting' . $className)) {
                    $model->{'deleting' . $className}();
                }

            });
        }
    }

    /**
     * @param string $eventName
     * @param array $history
     * @param bool $wasRecentlyCreated
     */
    public function invalidate(string $eventName = 'saved', array $history = [], bool $wasRecentlyCreated = false)
    {
        if($this instanceof Model) {

            echo (($eventName === 'saved') ? 'Saving' : 'Deleting') . " entity " . get_class() . ' with ID ' . $this->{$this->getKeyName()} . "\n";

            $classNames = class_uses($this) + [static::class];

            foreach($classNames AS $className) {

                $className = basename(str_replace('\\', '/', $className));

                if(method_exists($this, 'invalidate' . $className)) {
                    $this->{'invalidate' . $className}($eventName, $history, $wasRecentlyCreated);
                }

            }

            if($eventName === 'saved') {
                $this->touch();
            } elseif($eventName === 'deleting') {
                $this->forceDelete();
                $this->markAsInvalidated();
            }
        }
    }

    /**
     * @return bool
     */
    public function isBeingInvalidated() : bool
    {
        if($this instanceof Model) {
            $flag = Cache::getInstance()->getEntityRecord(self::class, $this->{$this->getKeyName()}, 'isBeingInvalidated', false);

            return (is_bool($flag) && $flag === true);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function markAsBeingInvalidated() : bool
    {
        if($this instanceof Model) {
            return Cache::getInstance()->setEntityRecord(self::class, $this->{$this->getKeyName()}, 'isBeingInvalidated', true, 3600);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function markAsInvalidated() : bool
    {
        if($this instanceof Model) {

            if ($this->getAttribute('slug') !== null) {
                Cache::getInstance()->deleteEntityRecord(self::class, $this->slug, '');
            }

            return Cache::getInstance()->deleteEntityRecord(self::class, $this->{$this->getKeyName()}, 'isBeingInvalidated');

        }

        return false;
    }
}
