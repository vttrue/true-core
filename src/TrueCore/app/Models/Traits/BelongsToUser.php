<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 25.10.2019
 * Time: 13:39
 */

namespace TrueCore\App\Models\Traits;

use \TrueCore\App\Models\System\User;

/**
 * Trait BelongsToUser
 *
 * @property-read User|null $owner
 *
 * @package TrueCore\App\Models\Traits
 */
trait BelongsToUser
{
    public static function bootBelongsToUser()
    {
        //
    }

    protected function invalidateBelongsToUser(string $eventName, array $history = [], bool $wasRecentlyCreated = false) : void
    {
        //
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}