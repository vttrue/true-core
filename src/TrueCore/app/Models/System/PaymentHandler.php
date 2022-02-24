<?php

namespace TrueCore\App\Models\System;

use Illuminate\Database\Eloquent\Model;

/**
 * TrueCore\App\Models\System\PaymentHandler
 *
 * @property int $id
 * @property string $name
 * @property string $handler
 * @property array $params
 * @property int $sort_order
 * @property int $status
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentHandler whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentHandler whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentHandler whereHandler($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentHandler whereParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentHandler whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentHandler whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentHandler newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentHandler newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentHandler query()
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class PaymentHandler extends Model
{
}
