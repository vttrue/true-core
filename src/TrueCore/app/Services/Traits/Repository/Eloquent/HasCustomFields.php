<?php

namespace TrueCore\App\Services\Traits\Repository\Eloquent;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use TrueCore\App\Models\System\{CustomField, CustomFieldRelatedEntity};
use Illuminate\Support\Str;

use \Closure;

/**
 * Trait HasCustomFields
 *
 * @package TrueCore\App\Services\Traits\Repository\Eloquent
 */
trait HasCustomFields
{
    /**
     * @return array
     */
    public function getCustomFields(): array
    {
        return [
            'customFields'      => function () {
                return $this->getModel()->customFields->map(function (CustomField $v) {
                    return [
                        'id'               => $v->id,
                        'code'             => $v->code,
                        'validation_rules' => $v->pivot->validation_rules,
                        'settings'         => $v->pivot->settings,
                        'sortOrder'        => $v->pivot->sort_order,
                        'status'           => $v->pivot->status,
                        'createdAt'        => (($v->created_at instanceof Carbon) ? $v->created_at->format('Y-m-d H:i:s') : null),
                        'updatedAt'        => (($v->updated_at instanceof Carbon) ? $v->updated_at->format('Y-m-d H:i:s') : null),
                    ];
                })->toArray();
            },
            'pivotCustomFields' => function () {
                return $this->getModel()->pivotCustomFields->map(function (CustomFieldRelatedEntity $v) {
                    return [
                        'id'              => $v->id,
                        'customField'     => [
                            'id'   => $v->customField->id,
                            'code' => $v->customField->code,
                        ],
                        'customFieldType' => [
                            'id'   => $v->customFieldType->id,
                            'code' => $v->customFieldType->name,
                        ],
                        'validationRules' => $v->validation_rules,
                        'settings'        => $v->settings,
                        'sortOrder'       => $v->sort_order,
                        'status'          => $v->status,
                        'createdAt'       => (($v->created_at instanceof Carbon) ? $v->created_at->format('Y-m-d H:i:s') : null),
                        'updatedAt'       => (($v->updated_at instanceof Carbon) ? $v->updated_at->format('Y-m-d H:i:s') : null),
                    ];
                })->toArray();
            },
        ];
    }

    /**
     * @param array $customFieldList
     */
    public function saveCustomFields(array $customFieldList)
    {
//        $fields = [];
//
//        foreach ($customFieldList as $k => $item) {
//            $fields[$k]['code'] = $item['code'];
//        }
    }
}
