<?php

namespace TrueCore\App\Models\System;

use Illuminate\Database\Eloquent\Model;

/**
 * TrueCore\App\Models\System\Setting
 *
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string $value
 * @property int $json
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting query()
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Setting extends Model
{
    protected $table = 'settings';
    protected $guarded = [];

    protected $casts = [
        'json'          => 'boolean',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime'
    ];

    public function saveSettings($data, $group = 'config')
    {
        foreach ($data as $key => $value) {

            if (is_array($value)) {
                $record = [
                    'group' => $group,
                    'key'   => $key,
                    'value' => json_encode($value, JSON_UNESCAPED_SLASHES),
                    'json'  => 1,
                ];
            } else {
                $record = [
                    'group' => $group,
                    'key'   => $key,
                    'value' => $value,
                    'json'  => 0,
                ];
            }

            $this->updateOrCreate(
                [
                    'group' => $group,
                    'key'   => $key,
                ],
                $record);
        }
    }
}
