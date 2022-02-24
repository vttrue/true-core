<?php

namespace TrueCore\App\Models\System;

use Illuminate\Database\Eloquent\Model;

/**
 * TrueCore\App\Models\System\Entity
 *
 * @property int $id
 * @property string $namespace
 * @property string $name
 * @property string $controller
 * @property string|null $policy
 * @property int $sort_order
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Entity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Entity whereNamespace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Entity whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Entity whereController($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Entity wherePolicy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Entity whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Entity whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Entity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Entity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Entity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Entity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Entity query()
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Entity extends Model
{
}
