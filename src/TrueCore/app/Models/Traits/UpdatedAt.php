<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 14.01.2019
 * Time: 13:23
 */

namespace TrueCore\App\Models\Traits;

use \TrueCore\App\Jobs\ClearEntityCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait UpdatedAt
 *
 * @package TrueCore\App\Models\Traits
 */
trait UpdatedAt
{
    use Invalidatable;

    public static function bootUpdatedAt()
    {
        if(method_exists(static::class, 'saved')) {

            static::saved(function(Model $model) {
                //dump(get_class($model) . ' - UpdatedAt');

                $changes = $model->getChanges();
                $changes = array_filter($changes, function($k) {
                    return ($k !== 'updated_at');
                }, ARRAY_FILTER_USE_KEY);

                if($model->wasRecentlyCreated === true || count($changes) > 0) {

                    if(method_exists($model, 'isBeingInvalidated') && $model->isBeingInvalidated() === false) {

                        if(method_exists($model, 'markAsBeingInvalidated')) {
                            $model->markAsBeingInvalidated();
                        }

                        ClearEntityCache::dispatch($model, 'saved', [], $model->wasRecentlyCreated, (($model->wasRecentlyCreated) ? time() : null));

                    }

                } elseif(method_exists($model, 'markAsInvalidated')) {
                    $model->markAsInvalidated();
                }

            });

        }

        if(method_exists(static::class, 'deleting')) {
            static::deleting(function(Model $model) {
                if(property_exists($model, 'forceDeleting') && $model->forceDeleting === false) {
                    ClearEntityCache::dispatch($model, 'deleting', [], false);
                }
            });
        }

    }
}