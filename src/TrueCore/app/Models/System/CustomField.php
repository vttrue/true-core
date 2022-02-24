<?php

namespace TrueCore\App\Models\System;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Support\{Carbon, Collection};
use TrueCore\App\Models\Traits\BelongsToUser;
use TrueCore\App\Models\System\User;

/**
 * TrueCore\App\Models\System\CustomField
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $code
 * @property int $sort_order
 * @property int $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $owner
 * @property-read CustomFieldEntity[]|Collection $entities
 * @method static Builder|CustomField whereId($value)
 * @method static Builder|CustomField whereUserId($value)
 * @method static Builder|CustomField whereCode($value)
 * @method static Builder|CustomField whereSortOrder($value)
 * @method static Builder|CustomField whereStatus($value)
 * @method static Builder|CustomField whereCreatedAt($value)
 * @method static Builder|CustomField whereUpdatedAt($value)
 * @method static Builder|CustomField newModelQuery()
 * @method static Builder|CustomField newQuery()
 * @method static Builder|CustomField query()
 * @mixin Builder
 */
class CustomField extends Model
{
    use BelongsToUser;

    protected $table = 'custom_fields';

    protected $fillable = ['code', 'sort_order', 'status', 'created_at', 'updated_at'];

    protected $hidden = [];

    protected $casts = ['user_id' => 'int', 'status' => 'boolean'];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function entities()
    {
        return $this->hasMany(CustomFieldRelatedEntity::class, 'custom_field_id');
    }
}
