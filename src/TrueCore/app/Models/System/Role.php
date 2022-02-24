<?php

namespace TrueCore\App\Models\System;

use \TrueCore\App\Models\Traits\BelongsToUser;

use Illuminate\Database\Eloquent\Model;

/**
 * TrueCore\App\Models\System\Role
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $owner
 * @property-read \Illuminate\Database\Eloquent\Collection|User[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|Entity[] $entities
 * @property-read int|null $users_count
 * @property-read int|null $entities_count
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role query()
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Role extends Model
{
    use BelongsToUser;

    protected $guarded = [];

    protected $fillable = [
        'user_id',
        'name',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entities()
    {
        return $this->belongsToMany(Entity::class, 'role_entity')
            ->withPivot(['permissions']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }
}
