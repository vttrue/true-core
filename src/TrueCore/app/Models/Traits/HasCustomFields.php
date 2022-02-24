<?php

namespace TrueCore\App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Expression;
use TrueCore\App\Models\System\CustomField;
use TrueCore\App\Models\System\CustomFieldRelatedEntity;

/**
 * Trait HasCustomFields
 *
 * @package TrueCore\App\Models\Traits
 */
trait HasCustomFields
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function customFields()
    {
        return $this->morphToMany(
            CustomField::class,
            'related_entity',
            'custom_field_related_entity',
            'related_entity_id',
            'custom_field_id',
            'id',
            'id'
        )->withPivot(['custom_field_type_id', 'validation_rules', 'settings', 'sort_order', 'status']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function pivotCustomFields()
    {
        $morphMapRelatedEntityName = Relation::$morphMap;

        if (is_array($morphMapRelatedEntityName) && count($morphMapRelatedEntityName) > 0) {

            $currentMorphAlias = array_search(static::class, $morphMapRelatedEntityName);

            if ($currentMorphAlias !== false) {
                return $this->hasMany(CustomFieldRelatedEntity::class, 'related_entity_id', 'id')
                    ->where('related_entity_type', '=', $currentMorphAlias);
            }
        }

        return $this->hasMany(CustomFieldRelatedEntity::class, 'related_entity_id', 'id');
    }
}
