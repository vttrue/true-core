<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 26.10.2018
 * Time: 16:43
 */

namespace TrueCore\App\Services\Traits\Repository\Eloquent;

use \TrueCore\App\Services\Traits\Exceptions\SortableException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

use Illuminate\Support\Str;

/**
 * Trait Sortable
 *
 * @property array|null $sortableFields
 * @property array|null $relationFields
 *
 * @package TrueCore\App\Services\Traits\Repository\Eloquent
 */
trait Sortable
{
    /**
     * @param Builder $builder
     * @param array $sort
     * @return Builder
     * @throws SortableException
     */
    protected function sortQuery(Builder $builder, array $sort = [])
    {
        if (count($sort)) {
            $sortableFields = $this->getSortableFields();
            $relationFields = $this->getRelationFields();

            foreach ($sort AS $field => $direction) {

                if (is_string($direction)) {

                    if (is_string($field)) {
                        $sortOn = $field;
                        $sortWay = ((in_array(strtoupper($direction), ['ASC', 'DESC'])) ? $direction : 'ASC');
                    } else {
                        $sortOn = $direction;
                        $sortWay = 'ASC';
                    }

                    $sortOnSnake = Str::snake($sortOn);

                    if (in_array($sortOn, $sortableFields) || in_array($sortOnSnake, $sortableFields)) {

                        $sortOn = $sortOnSnake;

                        if($builder->getQuery()->columns === null) {
                            $builder->addSelect($builder->qualifyColumn('*'));
                        } else {
                            $builder->select(array_map(function($v) use ($builder) {
                                return ((strpos($v, '.') === false) ? $builder->qualifyColumn($v) : $v);
                            }, $builder->getQuery()->columns));
                        }

                        if (array_key_exists($sortOn, $relationFields)) {
                            if (!is_array($relationFields[$sortOn])) {
                                throw new SortableException('Invalid relation specified for ' . $sortOn . ' field.');
                            } else {
                                if (!array_key_exists('relation', $relationFields[$sortOn]) || !is_string($relationFields[$sortOn]['relation'])) {
                                    throw new SortableException('Invalid relation specified for ' . $sortOn . ' field.');
                                } else if (!array_key_exists('fields', $relationFields[$sortOn]) || !is_array($relationFields[$sortOn]['fields']) || count($relationFields[$sortOn]['fields']) === 0) {
                                    throw new SortableException('Invalid relation specified for ' . $sortOn . ' field.');
                                }
                            }

                            if(array_key_exists('table', $relationFields[$sortOn]) && array_key_exists('foreign', $relationFields[$sortOn]) && array_key_exists('local', $relationFields[$sortOn]) && is_string($relationFields[$sortOn]['table']) && is_string($relationFields[$sortOn]['foreign']) && is_string($relationFields[$sortOn]['local'])) {
                                $builder->leftJoin($relationFields[$sortOn]['table'], $relationFields[$sortOn]['foreign'], $relationFields[$sortOn]['local']);

                                foreach ($relationFields[$sortOn]['fields'] AS $relField) {
                                    $builder->orderBy($relField, $sortWay);
                                }

                                if($builder->getQuery()->columns === null) {
                                    $builder->addSelect($builder->qualifyColumn('*'));
                                } else {
                                    $builder->select(array_map(function($v) use ($builder) {
                                        return ((strpos($v, '.') === false) ? $builder->qualifyColumn($v) : $v);
                                    }, $builder->getQuery()->columns));
                                }

                            } else {
                                // @TODO: refactor models' class names to eloquent compatible in order to avoid flexing with the foreign keys | deprecator @ 2018-11-09
                                //$relationTable = $builder->getRelation($relationFields[$sortOn]['relation'])->getModel()->getTable();
                                //dd([$builder->getRelation($relationFields[$sortOn]['relation'])->getModel()->getQualifiedKeyName(), $builder->getModel()->getForeignKey()]);

                                $model = $builder->getModel();
                                $relModel = clone $model;
                                $relTable = $relModel->getTable();

                                $relationChain = explode('.', $relationFields[$sortOn]['relation']);

                                foreach($relationChain AS $relation) {

                                    $prevRelTable = $relModel->getTable();
                                    $relationInstance = $relModel->with($relation)->getRelation($relation);

                                    $relModel = $relationInstance->getRelated();
                                    $relTable = $relModel->getTable();

                                    if($relationInstance instanceof BelongsTo) {

                                        $firstKey = $builder->qualifyColumn($prevRelTable . '.' . $relModel->getForeignKey());
                                        $secondKey = $builder->qualifyColumn($relModel->getQualifiedKeyName());

                                    } else if($relationInstance instanceof HasOneOrMany) {

                                        $firstKey = $builder->qualifyColumn($relationInstance->getQualifiedForeignKeyName());
                                        $secondKey = $builder->qualifyColumn($prevRelTable . '.' . $relModel->getKeyName());

                                    } else {
                                        throw new SortableException('Sorting via nested relations is only allowed with either BelongsTo or HasOneOrMany');
                                    }

//                                    $builder->leftJoin(
//                                        $relTable,
//                                        $firstKey,
//                                        '=',
//                                        $secondKey
//                                    );

                                    $builder->leftJoin($relTable, function(\Illuminate\Database\Query\JoinClause $v) use($relationInstance, $firstKey, $secondKey) {
                                        $relationWhereList = $relationInstance->getQuery()->getQuery()->wheres;

                                        $v->on($firstKey, '=', $secondKey);

                                        if(count($relationWhereList) > 0) {
                                            foreach($relationWhereList AS $relationWhere) {
                                                $v->where($relationInstance->qualifyColumn($relationWhere['column']), $relationWhere['operator'], $relationWhere['value'], $relationWhere['boolean']);
                                            }
                                        }
                                    });

//                                    $relationWhereList = $relationInstance->getQuery()->getQuery()->wheres;
//
//                                    if(count($relationWhereList) > 0) {
//                                        foreach($relationWhereList AS $relationWhere) {
//                                            $builder->where($relationInstance->qualifyColumn($relationWhere['column']), $relationWhere['operator'], $relationWhere['value'], $relationWhere['boolean']);
//                                        }
//                                    }

                                }

                                foreach ($relationFields[$sortOn]['fields'] AS $relField) {
                                    $builder->orderBy($builder->qualifyColumn($relTable . '.' . $relField), $sortWay);
                                }

                                //dd($builder->toSql());

//                                $builder->whereHas($relationFields[$sortOn]['relation'], function (Builder $builder) use ($relationFields, $sortOn, $sortWay) {
//
//                                    foreach ($relationFields[$sortOn]['fields'] AS $relField) {
//                                        $builder->orderBy($relField, $sortWay);
//                                    }
//
//                                });
                            }
                        } else {

                            if (method_exists($builder->getModel(), $scope = 'scope'.ucfirst(Str::camel($sortOn)))) {
                                $builder->scopes([Str::camel($sortOn) => []]);
                            }

                            $builder->orderBy($sortOn, $sortWay);

                        }
                    } else {
                        throw new SortableException('Sorting on ' . $sortOn . ' field is not allowed.');
                    }
                }
            }
        }

        return $builder;
    }

    /**
     * @return array
     */
    private function getSortableFields(): array
    {
        return ((property_exists($this, 'sortableFields') && is_array($this->sortableFields)) ? $this->sortableFields : []);
    }

    /**
     * @return array
     */
    private function getRelationFields(): array
    {
        return ((property_exists($this, 'relationFields') && is_array($this->relationFields)) ? $this->relationFields : []);
    }
}
