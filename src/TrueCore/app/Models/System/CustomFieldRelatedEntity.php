<?php

namespace TrueCore\App\Models\System;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Support\Carbon;

/**
 * App\Models\System\CustomField
 *
 * @property int $id
 * @property int $entity_id
 * @property int $custom_field_id
 * @property int $custom_field_type_id
 * @property array $validation_rules
 * @property array $settings
 * @property int $sort_order
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Entity|null $entity
 * @property-read CustomField|null $customField
 * @property-read CustomFieldType|null $customFieldType
 * @method static Builder|CustomFieldRelatedEntity whereId($value)
 * @method static Builder|CustomFieldRelatedEntity whereEntityId($value)
 * @method static Builder|CustomFieldRelatedEntity whereCustomFieldId($value)
 * @method static Builder|CustomFieldRelatedEntity whereCustomFieldTypeId($value)
 * @method static Builder|CustomFieldRelatedEntity whereValidationRules($value)
 * @method static Builder|CustomFieldRelatedEntity whereSettings($value)
 * @method static Builder|CustomFieldRelatedEntity whereSortOrder($value)
 * @method static Builder|CustomFieldRelatedEntity whereStatus($value)
 * @method static Builder|CustomFieldRelatedEntity whereCreatedAt($value)
 * @method static Builder|CustomFieldRelatedEntity whereUpdatedAt($value)
 * @method static Builder|CustomFieldRelatedEntity newModelQuery()
 * @method static Builder|CustomFieldRelatedEntity newQuery()
 * @method static Builder|CustomFieldRelatedEntity query()
 * @mixin Builder
 */
class CustomFieldRelatedEntity extends Model
{
    protected $table = 'custom_field_related_entity';

    protected $fillable = ['entity_id', 'custom_field_id', 'custom_field_type_id', 'validation_rules', 'settings', 'sort_order', 'status'];

    protected $hidden = [];

    protected $casts = [
        'id'                   => 'int',
        'entity_id'            => 'int',
        'custom_field_id'      => 'int',
        'custom_field_type_id' => 'int',
        'validation_rules'     => 'json',
        'settings'             => 'json',
        'sort_order'           => 'int',
        'status'               => 'boolean',
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function customField()
    {
        return $this->belongsTo(CustomField::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customFieldType()
    {
        return $this->belongsTo(CustomFieldType::class);
    }
}
