<?php

namespace TrueCore\App\Models\System;

use \TrueCore\App\Models\Traits\BelongsToUser;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * TrueCore\App\Models\System\User
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $role_id
 * @property string $name
 * @property string $phone
 * @property string $email
 * @property int|boolean $is_editable
 * @property string|null $password
 * @property string|null $remember_token
 * @property int|boolean $status
 * @property string|\Illuminate\Support\Carbon|null $last_visit_at
 * @property \Illuminate\Support\Carbon|string|null $created_at
 * @property \Illuminate\Support\Carbon|string|null $updated_at
 * @property-read User|null $owner
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read Role $role
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastVisitAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsEditable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;
    use BelongsToUser;

    protected $attributes = [
        'status' => 0,
    ];

    protected $guarded = [
        'id',
    ];

    protected $fillable = [
        'user_id',
        'role_id',
        'name',
        'phone',
        'email',
        'is_editable',
        'status',
        'last_visit_at',
    ];

    protected $casts = [
        'is_editable' => 'boolean',
        'status'      => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'last_visit_at',
    ];

    /**
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @param $value
     */
    public function setLastVisitAtAttribute($value)
    {
        try {
            $date = (new Carbon($value));
        } catch (\Throwable $e) {
            $date = null;
        }

        $this->attributes['last_visit_at'] = $date;
    }
}
