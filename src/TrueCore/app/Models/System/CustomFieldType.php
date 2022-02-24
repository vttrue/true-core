<?php

namespace TrueCore\App\Models\System;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Support\Carbon;

/**
 * TrueCore\App\Models\System\CustomField
 *
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property int $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|CustomField whereId($value)
 * @method static Builder|CustomField whereName($value)
 * @method static Builder|CustomField whereSortOrder($value)
 * @method static Builder|CustomField whereStatus($value)
 * @method static Builder|CustomField whereCreatedAt($value)
 * @method static Builder|CustomField whereUpdatedAt($value)
 * @method static Builder|CustomField newModelQuery()
 * @method static Builder|CustomField newQuery()
 * @method static Builder|CustomField query()
 * @mixin Builder
 */
class CustomFieldType extends Model
{
    protected $table = 'custom_field_types';

    protected $fillable = ['name', 'sort_order', 'status', 'created_at', 'updated_at'];

    protected $hidden = [];

    protected $casts = ['status' => 'boolean'];

    protected $dates = ['created_at', 'updated_at'];
}
