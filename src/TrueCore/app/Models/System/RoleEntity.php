<?php

namespace TrueCore\App\Models\System;

use Illuminate\Database\Eloquent\Model;

/**
 * TrueCore\App\Models\System\RoleEntity
 *
 * @property int $role_id
 * @property int $entity_id
 * @property array $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection|Entity[] $entity
 * @property-read \Illuminate\Database\Eloquent\Collection|Role[] $role
 * @method static \Illuminate\Database\Eloquent\Builder|RoleEntity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoleEntity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoleEntity query()
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class RoleEntity extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'role_id',
        'entity_id',
        'permissions'
    ];

    protected $casts = [
        'permissions' => 'array'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class, 'entity_id', 'id');
    }
}
