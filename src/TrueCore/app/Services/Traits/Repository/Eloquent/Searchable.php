<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 26.10.2018
 * Time: 18:00
 */

namespace TrueCore\App\Services\Traits\Repository\Eloquent;

use \TrueCore\App\Services\Traits\Exceptions\SearchableException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Trait Searchable
 *
 * @property array|null $searchableFields
 * @property array|null $relationFields
 *
 * @package TrueCore\App\Services\Traits\Repository\Eloquent
 */
trait Searchable
{
    /**
     * @param Builder $builder
     * @param array $search
     * @return Builder
     * @throws SearchableException
     *
     * @TODO: implement field type checking to search on JSON fields properly | deprecator @ 2018-10-26
     */
    protected function searchQuery(Builder $builder, array $search = [])
    {
        if(count($search)) {
            $searchableFields = $this->getSearchableFields();
            $relationFields = $this->getRelationFields();

            foreach($search AS $field => $fieldParam) {
                if(!is_string($field)) {
                    throw new SearchableException('Numeric field specified.');
                }

                // splitBy always defaults to the whitespace character unless set to null or any string character
                // wildCard always defaults to null unless set to any string character. Only useful with the LIKE operator.
                if(is_array($fieldParam)) {

                    $string = ((array_key_exists('param', $fieldParam)) ? $fieldParam['param'] : '');
                    $operator = ((array_key_exists('operator', $fieldParam)) ? $fieldParam['operator'] : 'LIKE');
                    $splitBy = ((array_key_exists('splitBy', $fieldParam)) ? $fieldParam['splitBy'] : ' ');
                    $wildCard = ((array_key_exists('wildCard', $fieldParam)) ? $fieldParam['wildCard'] : null);

                } else {

                    $string = $fieldParam;
                    $operator = 'LIKE';
                    $splitBy = ' ';
                    $wildCard = null;

                }

                if(!is_string($operator) || !in_array(strtoupper($operator), ['=', 'LIKE'])) {
                    throw new SearchableException('Only = and LIKE operators are supported.');
                }

                if($wildCard !== null && !is_string($wildCard)) {
                    throw new SearchableException('Wildcard parameter may either be of type String or NULL.');
                }

                if(!is_string($string)) {
                    throw new SearchableException('Invalid search string specified. Must be of type String.');
                }

                $fieldSnake = Str::snake($field);

                if((!in_array($field, $searchableFields) && (!array_key_exists($field, $searchableFields) || !is_array($searchableFields[$field]))) === true
                    || (!in_array($fieldSnake, $searchableFields) && (!array_key_exists($fieldSnake, $searchableFields) || !is_array($searchableFields[$fieldSnake]))) === true ) {
                    throw new SearchableException('Searching on ' . $field . ' field is not allowed.');
                }

                $field = $fieldSnake;

                if(strtoupper($operator) === 'LIKE') {

                    if ($wildCard !== null && $wildCard !== '') {

                        if (strpos($string, $wildCard) !== false) {
                            $string = str_replace($wildCard, '%', $string);
                        } else {
                            $wildCard = null;
                        }

                    } else {
                        $wildCard = null;
                    }

                } else {
                    $wildCard = null;
                }

                if(array_key_exists($field, $relationFields)) {
                    if(!is_array($relationFields[$field])) {
                        throw new SearchableException('Invalid relation specified for ' . $field . ' field.');
                    } else {
                        if(!array_key_exists('relation', $relationFields[$field]) || !is_string($relationFields[$field]['relation'])) {
                            throw new SearchableException('Invalid relation specified for ' . $field . ' field.');
                        } else if(!array_key_exists('fields', $relationFields[$field]) || !is_array($relationFields[$field]['fields']) || count($relationFields[$field]['fields']) === 0) {
                            throw new SearchableException('Invalid relation specified for ' . $field . ' field.');
                        }
                    }

                    $builder->whereHas($relationFields[$field]['relation'], function (Builder $builder) use ($relationFields, $field, $string, $operator, $splitBy) {

                        $builder->where(function(Builder $query) use($relationFields, $field, $string, $operator, $splitBy) {

                            foreach($relationFields[$field]['fields'] AS $relField) {

                                $query->orWhere(function(Builder $query) use ($relField, $string, $operator, $splitBy) {

                                    if($splitBy !== null && $splitBy !== '') {
                                        $strParts = explode(' ', $string);
                                    } else {
                                        $strParts = [$string];
                                    }

                                    foreach($strParts AS $strPart) {
                                        $query->orWhere(function(Builder $query) use($strPart, $relField, $operator) {
                                            $query->whereNotNull($query->qualifyColumn($relField))
                                                ->where($query->qualifyColumn($relField), '!=', '')
                                                ->where($query->qualifyColumn($relField), $operator, ((strtoupper($operator) === 'LIKE') ? '%' . $strPart . '%' : $strPart));
                                        });
                                    }

                                });

                            }

                        });

                    });

                } else {

                    if($splitBy !== null & $splitBy !== '') {
                        $strParts = explode(' ', $string);
                    } else {
                        $strParts = [$string];
                    }

                    // @TODO: extend Eloquent\Builder in order to redeclare countForPagination method to exclude having statement out of the query | deprecator @ 2018-11-10
                    if (!Schema::hasColumn($builder->getModel()->getTable(), $field)) {

                        if(array_key_exists($field, $searchableFields) && is_array($searchableFields[$field])) {

                            $builder->where(function (Builder $query) use ($field, $strParts, $searchableFields, $operator, $wildCard) {

                                foreach($searchableFields[$field] AS $subField) {

                                    $query->orWhere(function (Builder $query) use ($subField, $strParts, $operator, $wildCard) {

                                        foreach ($strParts AS $strPart) {

                                            if($wildCard === null) {
                                                $query->orWhere($query->qualifyColumn($subField), '=', $strPart);
                                            }

                                            $query->orWhere($query->qualifyColumn($subField), $operator, '%' . $strPart . '%');
                                        }

                                    });

                                }

                            });

                        } else {
                            // @TODO: throw an Exception because we don't want incorrect parameters to be used at all | deprecator @ 2019-03-27
                        }

                        // @TODO: deal with scope searching | deprecator @ 2019-03-27
                        if(method_exists($builder->getModel(), $scope = 'scope'.ucfirst(Str::camel($field)))) {

                            $builder->scopes([Str::camel($field) => [$strParts]]);

                        }

                    } else {

                        $builder->where(function (Builder $query) use ($field, $strParts, $operator, $wildCard) {

                            $query->orWhere(function (Builder $query) use ($field, $strParts, $operator, $wildCard) {

                                foreach ($strParts AS $strPart) {

                                    if($wildCard === null) {
                                        $query->orWhere($query->qualifyColumn($field), '=', $strPart);
                                    }

                                    $query->orWhere($query->qualifyColumn($field), $operator, '%' . $strPart . '%');
                                }

                            });

                        });

                    }

                }
            }
        }

        return $builder;
    }

    /**
     * @return array
     */
    private function getSearchableFields() : array
    {
        return ((property_exists($this, 'searchableFields') && is_array($this->searchableFields)) ? $this->searchableFields : []);
    }

    /**
     * @return array
     */
    private function getRelationFields() : array
    {
        return ((property_exists($this, 'relationFields') && is_array($this->relationFields)) ? $this->relationFields : []);
    }
}
