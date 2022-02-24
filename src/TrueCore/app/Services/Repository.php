<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 20.09.2019
 * Time: 1:01
 */

namespace TrueCore\App\Services;

use Illuminate\Support\Collection;
use \TrueCore\App\Services\Interfaces\Repository as RepositoryInterface;
use \Closure;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use \TrueCore\App\Services\Traits\Exceptions\{
    ModelDeleteException,
    ModelSaveException
};
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Eloquent\{
    Builder,
    Model,
    Relations\Relation
};
use \Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use \TrueCore\App\Services\Traits\Repository\Eloquent\{
    Searchable,
    Sortable
};

/**
 * Class Repository
 *
 * @package TrueCore\App\Services
 */
abstract class Repository implements RepositoryInterface
{
    use Searchable, Sortable {
        Searchable::getRelationFields insteadof Sortable;
    }

    protected Model $model;
    protected static ?Dispatcher $modelEventDispatcher = null;
    protected array $modelEvents = [];
    protected array $bootedModels = [];

    protected array $normalizedParams = [];

    protected bool $isSaving = false;

    protected array $switchableFields = [];

    protected array $eagerRelations = [];
    protected array $eagerCountRelations = [];

    protected array $scopeFields          = [];

    protected static int $savingDepth     = 0;

    /**
     * Repository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param Dispatcher $dispatcher
     */
    protected function setModelEventDispatcher(Dispatcher $dispatcher): void
    {
        self::$modelEventDispatcher = $dispatcher;
    }

    /**
     * @return Dispatcher|null
     */
    protected function getModelEventDispatcher(): ?Dispatcher
    {
        return self::$modelEventDispatcher;
    }

    /**
     * @param Model $model
     * @param string $event
     */
    protected function addModelEvent(Model $model, string $event): void
    {
        $this->modelEvents[get_class($model) . '-' . $model->getKey() . '-' . $event] = [
            'event' => $event,
            'model' => $model,
        ];
    }

    /**
     * @param Model $model
     * @param string $action
     *
     * @return bool
     */
    private function processModel(Model $model, string $action): bool
    {
        if (!in_array($action, ['delete', 'save'])) {
            // @TODO: Exception | Deprecator @ 2020-02-10
            return false;
        }

        $model::setEventDispatcher($this->getModelEventDispatcher());

        $this->getModelEventDispatcher()->listen('eloquent.processedWithoutEvents: ' . get_class($model), function (Model $model) {
            $model::setEventDispatcher($this->getModelEventDispatcher());
            if (!in_array(get_class($model), $this->bootedModels)) {
                //dump(['Booted: ' . get_class($model) . ' - ' . $model->getKey()]);
                $model::boot();
                $this->bootedModels[] = get_class($model);
            }
            //dump(['ProcessedWithoutEvents: ' . get_class($model) . ' - ' . $model->id, ($model::getEventDispatcher() !== null)]);
            $this->addModelEvent($model, 'saved');
            $model::unsetEventDispatcher();
        });

        $isProcessed = $model->{$action}();

        if ($isProcessed === true) {
            $this->addModelEvent($model, $action . 'd');
        }

        $model::unsetEventDispatcher();

        $this->getModelEventDispatcher()->dispatch('eloquent.processedWithoutEvents: ' . get_class($model), $model);

        $this->getModel()->setRelations([]);

        return $isProcessed;
    }

    /**
     * @param Model $model
     *
     * @return bool
     */
    protected function saveModel(Model $model): bool
    {
        return $this->processModel($model, 'save');
    }

    /**
     * @param Model $model
     *
     * @return bool
     */
    protected function deleteModel(Model $model): bool
    {
        return $this->processModel($model, 'delete');
    }

    /**
     * NormalizeData with @property $normalizedParams, where params array with key ID
     * @param array $data
     *
     * @return array
     */
    protected function normalizeData(array $data): array
    {
        foreach ($this->normalizedParams AS $param => $key) {

            if (is_string($param) === false) {
                $param = $key;
                $key = 'id';
            }

            if (array_key_exists($param, $data) === true) {


                if (
                    is_array($data[$param]) &&
                    array_key_exists($key, $data[$param]) &&
                    (
                        (
                            is_numeric($data[$param][$key]) &&
                            (int)$data[$param][$key] > 0
                        ) ||
                        $data[$param][$key] === null
                    )
                ) {
                    $data[$param . ucfirst($key)] = (($data[$param][$key] !== null) ? (int)$data[$param][$key] : null);
                } elseif ($data[$param] === null) {
                    $data[$param . ucfirst($key)] = null;
                }
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @param callable|null $successCallback
     * @param callable|null $failCallback
     *
     * @return bool
     *
     * @throws ModelSaveException
     */
    private function handleModification(array $data, ?callable $successCallback = null, ?callable $failCallback = null): bool
    {
        try {

            $dispatcher = static::getModelEventDispatcher();
            $wasSaving = false;
            $success = false;

            self::$savingDepth++;

            if ($this->isSaving === false) {

                $this->isSaving = true;

                if (self::$savingDepth === 1) {
                    DB::beginTransaction();
                }

                if ($dispatcher === null) {
                    /** @var Dispatcher $dispatcher */
                    $dispatcher = $this->getModel()::getEventDispatcher();
                }

                if ($dispatcher !== null) {
                    $this->setModelEventDispatcher($dispatcher);
                    $this->addModelEvent($this->getModel(), 'saved');

                    $this->getModel()::unsetEventDispatcher();
                }
            } else {
                $wasSaving = true;
            }

            try {

                $this->model = $this->getModel()->fill(_snakeCaseArrayKeys($this->normalizeData($data)));

                if ($dispatcher !== null && $wasSaving === false) {
                    $dispatcher->dispatch('eloquent.saving: ' . get_class($this->getModel()), $this->getModel());
                }

                if ($this->getModel()->saveOrFail()) {

                    $this->processModification($this->normalizeData($data));

                    // We have to reset all previously eagerly loaded relations
                    // To make sure they won't suddenly become unavailable during any of the next saving related routines
                    //
                    // @link https://stackoverflow.com/questions/39520072/laravel-relationship-availability-after-save
                    //
                    // @TODO: check if there is a more appropriate workaround | Deprecator @ 2020-02-16
                    $this->getModel()->setRelations([]);

                    if ($wasSaving === false) {

                        if ($successCallback instanceof Closure) {
                            $successCallback();
                        }

                        //dd(array_map(function($v) {return ['event' => $v['event'], 'model' => get_class($v['model'])];}, $this->modelEvents));

                        if (self::$savingDepth === 1) {
                            DB::commit();
                        }

                        $this->isSaving = false;

                        $success = true;

                    }

                    return true;

                }

            } finally {

                if ($this->isSaving === false && $dispatcher !== null) {
                    $this->getModel()::setEventDispatcher($dispatcher);
                }

                if ($success === true) {
                    foreach ($this->modelEvents AS $modelEventEntry) {
                        //dump(get_class($modelEventEntry['model']) . ' -- ' . $modelEventEntry['model']->id);
                        $this->getModelEventDispatcher()->dispatch('eloquent.' . $modelEventEntry['event'] . ': ' . get_class($modelEventEntry['model']), $modelEventEntry['model']);
                    }
                }

                self::$savingDepth--;

            }

            if ($failCallback instanceof Closure) {
                $failCallback();
            }

            $this->isSaving = false;

            DB::rollBack();

            return false;

        } catch (\Throwable $e) {

            if ($failCallback instanceof Closure) {
                $failCallback();
            }

            DB::rollBack();

            $this->isSaving = false;

            self::$savingDepth--;

            throw new ModelSaveException($e->getMessage() . ' | ' . $e->getFile() . ' | ' . $e->getLine(), 0, $e->getPrevious());
        }
    }

    /**
     * @param array $data
     */
    abstract protected function processModification(array $data = []): void;

    /**
     * @return bool
     */
    public function isSaving(): bool
    {
        return $this->isSaving;
    }

    /**
     * @param array $data
     * @param callable|null $successCallback
     * @param callable|null $failCallback
     *
     * @return RepositoryInterface
     *
     * @throws ModelSaveException
     */
    public function add(array $data, ?callable $successCallback = null, ?callable $failCallback = null)
    {
        $this->handleModification($data, $successCallback, $failCallback);

        return $this;
    }

    /**
     * @param array|null $fields
     * @param string $type
     *
     * @return array
     */
    private function processEagerRelationList(?array $fields = null, string $type = 'eagerRelations'): array
    {
        $relationList = [];

        if ($fields === null) {

            if (array_key_exists('*', $this->{$type})) {
                $relationList = $this->{$type}['*'];
            }

        } else {

            $fieldProcessor = function (string $field) use (&$relationList, $type) : array {

                if ($field === '*') {

                    $relationList = $this->{$type}[$field];

                } elseif (is_array($this->{$type}[$field])) {
                    $relationList = array_merge($relationList, $this->{$type}[$field]);
                } else {
                    // @TODO: Throw an Exception | Deprecator @ 2020-03-24
                }

                return $relationList;
            };

            foreach ($fields AS $field) {

                $asteriskField = null;

                if (array_key_exists($field, $this->{$type})) {
                    $relationList = $fieldProcessor($field);
                } elseif (($asteriskField = substr($field, 0, ((($dotPos = strrpos($field, '.')) !== false) ? $dotPos : strlen($field)))) !== null) {

                    if (array_key_exists($asteriskField . '.*', $this->{$type})) {
                        $relationList = $fieldProcessor($asteriskField . '.*');
                    } elseif (array_key_exists($asteriskField, $this->{$type})) {
                        $relationList = $fieldProcessor($asteriskField);
                    }

                }

            }

        }

        return array_unique($relationList);
    }

    /**
     * @param array|null $fields
     *
     * @return array
     */
    protected function getRelationListByFields(?array $fields = null): array
    {
        return $this->processEagerRelationList($fields, 'eagerRelations');
    }

    /**
     * @param array|null $fields
     *
     * @return array
     */
    protected function getCountRelationListByFields(?array $fields = null): array
    {
        return $this->processEagerRelationList($fields, 'eagerCountRelations');
    }

    /**
     * @param array $options
     *
     * @return bool|Builder
     * @throws \Exception
     */
    private function getAllQuery(array $options = [])
    {
        if (array_key_exists('parent', $options)
            && is_array($options['parent'])
            && array_key_exists('instance', $options['parent'])
            && array_key_exists('relation', $options['parent']) && is_string($options['parent']['relation'])) {

            $query = ($options['parent']['instance'])->{$options['parent']['relation']}()->getQuery();

        } else {
            $modelInstance = $this->getModel();

            $query = $modelInstance->newQuery();
        }

        if (is_array($this->eagerRelations) === true) {
            $query->with($this->getRelationListByFields(((array_key_exists('fields', $options) && is_array($options['fields']) && count($options['fields']) > 0) ? $options['fields'] : null)));
        } elseif (array_key_exists('relations', $options) && is_array($options['relations']) && count($options['relations']) > 0) {
            try {
                $query->with($options['relations']);
            } catch (\Throwable $e) {
                throw new \Exception($e->getMessage());
            }
        }

        if (is_array($this->eagerCountRelations) === true) {

            $eagerCountRelationList = $this->getCountRelationListByFields(((array_key_exists('fields', $options) && is_array($options['fields']) && count($options['fields']) > 0) ? $options['fields'] : null));

            $nestedCountList = [];

            $eagerCountRelationList = array_filter($eagerCountRelationList, static function (string $v) use (&$nestedCountList) : bool {

                $isNested = (strpos($v, '.') !== false);

                if ($isNested === true) {
                    $nestedCountList[] = $v;
                }

                return ($isNested === false);
            });

            $query->withCount($eagerCountRelationList);

            if (count($nestedCountList) > 0) {

                $nestedCountRelationList = [];

                foreach ($nestedCountList AS $nestedRelation) {
                    $baseRelation = substr($nestedRelation, 0, strrpos($nestedRelation, '.'));

                    if (array_key_exists($baseRelation, $nestedCountRelationList) === false) {
                        $nestedCountRelationList[$baseRelation] = [];
                    }

                    $nestedCountRelationList[$baseRelation][] = substr($nestedRelation, (strrpos($nestedRelation, '.') + 1));
                }

                krsort($nestedCountRelationList);

                foreach ($nestedCountRelationList AS $relationChain => $countRelation) {
                    $query->with([
                        $relationChain => static fn(Relation $relation): Relation => $relation->withCount($countRelation),
                    ]);
                }
            }

        } elseif (array_key_exists('countRelations', $options) && is_array($options['countRelations']) && count($options['countRelations']) > 0) {
            try {
                $query->withCount($options['countRelations']);
            } catch (\Throwable $e) {
                throw new \Exception($e->getMessage());
            }
        }

        $search = ((array_key_exists('search', $options) && is_array($options['search']))
            ? $options['search'] : []);

        $sort = ((array_key_exists('sort', $options) && is_array($options['sort']))
            ? $options['sort'] : []);

        if (count($search) || count($sort)) {

            $instance = $this;

            if (count($search)) {
                $query = $instance->searchQuery($query, $search);
            }

            if (count($sort)) {
                $query = $instance->sortQuery($query, $sort);
            }
        }

        if (array_key_exists('conditions', $options) && is_array($options['conditions'])) {

            /**
             * @param Builder $query
             * @param string $field
             * @param string $operator
             * @param $value
             *
             * @return Builder
             */
            $conditionProcessor = function (Builder $query, string $field, string $operator, $value) {

                $field = Str::snake($field);

                $operator = strtoupper($operator);

                if (in_array($operator, ['NOT IN', 'IN']) || (in_array($operator, ['=', '!=', '<>']) && is_array($value))) {

                    $fieldVal = ((is_array($value)) ? $value : (array)$value);

                    if (count($fieldVal) > 0) {

                        if (in_array($operator, ['=', 'IN'])) {
                            $query->whereIn($query->qualifyColumn($field), $fieldVal);
                        } else {
                            $query->whereNotIn($query->qualifyColumn($field), $fieldVal);
                        }
                    }

                } elseif (is_array($value) && count($value) === 2) {

                    if ($operator === 'NOT BETWEEN') {
                        $query->whereNotBetween($query->qualifyColumn($field), $value);
                    } else {
                        if ($operator === 'JSON_CONTAINS') {

                            if (is_array($value)) {

                                $query->where(function (Builder $builder) use ($field, $value) {
                                    foreach ($value as $k => $v) {
                                        if ($k === 0) {
                                            $builder->whereJsonContains($builder->qualifyColumn($field), [$v]);
                                        } else {
                                            $builder->orWhereJsonContains($builder->qualifyColumn($field), [$v]);
                                        }
                                    }
                                });
                            } else {
                                $query->whereJsonContains($query->qualifyColumn($field), [$value]);
                            }
                        } else {
                            $query->whereBetween($query->qualifyColumn($field), $value);
                        }
                    }
                } else {

                    if (Schema::hasColumn($query->getModel()->getTable(), $field) === true) {

                        /** @TODO: Согласовать с Жентосом | Incarnator 2020-02-06 */
                        if ($operator === 'JSON_CONTAINS') {

                            if (is_array($value)) {

                                $query->where(function (Builder $builder) use ($field, $value) {
                                    foreach ($value as $k => $v) {
                                        if ($k === 0) {
                                            $builder->whereJsonContains($builder->qualifyColumn($field), [$v]);
                                        } else {
                                            $builder->orWhereJsonContains($builder->qualifyColumn($field), [$v]);
                                        }
                                    }
                                });

                            } else {
                                $query->whereJsonContains($query->qualifyColumn($field), [$value]);
                            }
                        } elseif (in_array($operator, ['LIKE', 'RLIKE', 'SOUNDS LIKE'], true)) {
                            $query->where($query->qualifyColumn($field), $operator, (($operator === 'SOUNDS LIKE') ? $value : (($operator === 'LIKE') ? '%' . $value . '%' : $value)));
                        } else {
                            $query->where($query->qualifyColumn($field), $operator, $value);
                        }
                    } else {
                        if (method_exists($query->getModel(), $scope = 'scope' . ucfirst(Str::camel($field)))) {
                            $query->scopes([Str::camel($field) => [$operator, $value]]);
                        } else {
                            // @TODO: throw an Exception | Incarnator @ 2020-01-24
                        }
                    }
                }

                return $query;
            };

            $conditionParser = function ($conditionList, Builder $query, $boolean = 'AND') use (&$conditionParser, $conditionProcessor) {

                // Dealing with boolean AND & OR & RELATED blocks;
                // If none are present then we are considering current $conditionList as an ordinary condition block
                // And should treat it as such

                $andBlockList = array_merge(array_column($conditionList, 'AND'), array_column($conditionList, 'and'));
                $orBlockList = array_merge(array_column($conditionList, 'OR'), array_column($conditionList, 'or'));

                $relatedBlockList = array_merge(array_column($conditionList, 'RELATED'), array_column($conditionList, 'related'));

                if (count($andBlockList) > 0 || count($orBlockList) > 0 || count($relatedBlockList) > 0) {

                    // For the sake of short syntax usage, ffs | Deprecator @ 2020-03-02
                    // Any sibling block that hasn't been caught into one of $andBlockList, $orBlockList or $relatedBlockList should be considered as an AND BLOCK

                    $additionalAndBlockList = array_filter($conditionList, function ($v) {
                        return ((
                                array_key_exists('OR', $v) ||
                                array_key_exists('or', $v) ||
                                array_key_exists('AND', $v) ||
                                array_key_exists('and', $v) ||
                                array_key_exists('RELATED', $v) ||
                                array_key_exists('related', $v)) === false);
                    });

                    if (count($additionalAndBlockList) > 0) {

                        foreach ($additionalAndBlockList AS $additionalAndBlock) {
                            $query = $conditionParser($additionalAndBlock, $query, 'AND');
                        }

                    }

                    foreach ($andBlockList AS $andConditionList) {
                        $query->where(function (Builder $builder) use ($conditionParser, $andConditionList) {
                            return $conditionParser($andConditionList, $builder, 'AND');
                        }, null, null, 'AND');
                    }

                    foreach ($orBlockList AS $orConditionList) {
                        $query->where(function (Builder $builder) use ($conditionParser, $orConditionList) {
                            return $conditionParser($orConditionList, $builder, 'OR');
                        }, null, null, 'OR');
                    }

                    foreach ($relatedBlockList AS $relatedConditionList) {
                        //dd($relatedConditionList);
                        if (array_key_exists('PATH', $relatedConditionList) && is_string($relatedConditionList['PATH']) && array_key_exists('CONDITIONS', $relatedConditionList) && is_array($relatedConditionList['CONDITIONS'])) {
                            $query->whereHas($relatedConditionList['PATH'], function (Builder $builder) use ($conditionParser, $relatedConditionList) {
                                return $conditionParser($relatedConditionList['CONDITIONS'], $builder, 'AND');
                            });
                        } else {
                            // @TODO: throw an exception | Deprecator @ 2020-03-03
                        }
                    }

                    // All condition blocks have been processed, moving onto the next iteration (if any)

                    return $query;
                }

                //dd($query->getQuery()->toSql(), $conditionList);

                // Dealing with ordinary condition block elements only to prepare them for and pass next to the $conditionProcessor
                foreach ($conditionList AS $field => $value) {

                    if (strtoupper($field) === 'RELATED') {

                        // @TODO: check later if needed | Deprecator @ 2020-03-03
//                        if (array_key_exists('PATH', $value) && is_string($value['PATH']) && array_key_exists('CONDITIONS', $value) && is_array($value['CONDITIONS'])) {
//
//                            $query->whereHas($value['PATH'], function (Builder $builder) use ($conditionParser, $value) {
//                                return $conditionParser($value['CONDITIONS'], $builder, 'AND');
//                            });
//
//                            continue;
//
//                        } else {
//                            // @TODO: Throw an Exception "Invalid related query block" | Deprecator @ 2020-03-03
//                        }

                        continue;

                    }

                    if (is_numeric($field)) {

                        if (is_array($value)) {
                            $query->where(function (Builder $builder) use ($conditionParser, $value, $boolean) {
                                return $conditionParser($value, $builder, $boolean);
                            }, null, null, $boolean);
                        }

                        continue;
                    }

                    $operator = '=';
                    $val = null;

                    if (is_array($value)) {

                        if (count($value) === 2) {
                            [$operator, $val] = $value;

                            if (!is_string($operator) || !in_array(strtoupper($operator), ['!=', '<>', '=', '>', '<', '>=', '<=', 'IN', 'BETWEEN', 'JSON_CONTAINS', 'LIKE', 'RLIKE', 'SOUNDS LIKE'], true)) {
                                $operator = 'IN';
                                $val = $value;
                            }
                        } else {
                            $operator = 'IN';
                            $val = $value;
                        }

                    } else {
                        if (!is_array($value)) {
                            $val = $value;
                        } else {
                            throw new \Exception('Invalid condition for query');
                        }
                    }

                    // The presence of this commented-out condition is an old story which origins are as mysterious as our entire existence
                    //if ($val !== null) {

                    if (strpos($field, '.') !== false) {

                        $relationChain = explode('.', $field);

                        $relation = implode('.', array_slice($relationChain, 0, (count($relationChain) - 1)));
                        $relationField = end($relationChain);

                        // Checking if this might indeed be a valid standalone relation
                        // So the current query's model might be related by a foreign key
                        // Thus we don't have to make unnecessary sub queries
                        if (strpos($relation, '.') === false) {

                            $relationModel = $query->getRelation($relation)->getModel();
                            $relationFk = $relationModel->getForeignKey();

                            // If the foreign key exists within this particular model
                            // We'd be real idiots not taking advantage of using it
                            if (in_array($relationFk, [$relation . '_' . $relationField, strtolower(Str::snake(basename(str_replace('\\', '/', get_class($relationModel))))) . '_' . $relationField], true) && Schema::hasColumn($query->getModel()->getTable(), $relationFk) === true) {
                                $query = $conditionProcessor($query, $relationFk, $operator, $val);
                                continue;
                            }

                        }

                        $query->whereHas($relation, function ($builder) use ($conditionProcessor, $relationField, $operator, $val) {
                            return $conditionProcessor($builder, $relationField, $operator, $val);
                        });

                    } else {
//                            if ($query->getQuery()->from === 'shop_bundles' && $field !== 'starts_at') {
//                                dd($field, $val);
//                            }
//                            $query = $query->where(function (Builder $builder) use ($query, $field, $operator, $val, $conditionProcessor) {
//                                return $conditionProcessor($query, $field, $operator, $val);
//                            }, null, null, $boolean);
                        $query = $conditionProcessor($query, $field, $operator, $val);
                    }

                    //}
                }

                return $query;

            };

            $query = $conditionParser($options['conditions'], $query, 'AND');
            //dd($options['conditions'], $query->getQuery()->toSql(), $query->getQuery()->getBindings());

            // For debugging purpose
            //dd($query->getQuery()->toSql(), $query->getQuery()->getBindings());
            //print_r([$query->getQuery()->toSql(), $query->getQuery()->getBindings()]);die;
        }

        if (count($this->scopeFields) > 0) {
            $scopeList = ((array_key_exists('fields', $options) && is_array($options['fields']) && count($options['fields']) > 0) ? array_filter($this->scopeFields, static fn ($field) : bool => in_array($field, $options['fields']), ARRAY_FILTER_USE_KEY) : $this->scopeFields);

            if (count($scopeList) > 0) {
                $query->scopes($scopeList);
            }
        }

        // @TODO: obsolete? Should be removed? After very very accurate investigation of usage cases | Deprecator @ 2020-01-09
        // @deprecated in favor of the new query builder since 2020-03-04 | Deprecator
        if (array_key_exists('whereHas', $options) && is_array($options['whereHas'])) {

            $whereHasCallback = null;

            if (array_key_exists('condition', $options['whereHas']) && is_array($options['whereHas']['condition']) && array_key_exists('field', $options['whereHas']['condition']) && array_key_exists('value', $options['whereHas']['condition'])) {
                $whereHasCallback = function (Builder $query) use ($options) {
                    if (is_array($options['whereHas']['condition']['value'])) {
                        $query->whereIn($query->qualifyColumn($options['whereHas']['condition']['field']), $options['whereHas']['condition']['value']);
                    } else {
                        $query->where($query->qualifyColumn($options['whereHas']['condition']['field']), '=', $options['whereHas']['condition']['value']);
                    }
                };
            }

            $query->whereHas($options['whereHas']['relation'], $whereHasCallback);
        }

        // Debug
        //print_r([$query->getQuery()->toSql(), $query->getQuery()->getBindings()]);die;

        return $query;
    }

    /**
     * @param array $options
     * @param array $columns
     *
     * @return RepositoryInterface[]|array
     * @throws \Exception
     */
    public function getAll(array $options = [], array $columns = ['*'])
    {
        $query = $this->getAllQuery($options);

        if (array_key_exists('limit', $options) && is_numeric($options['limit'])) {
            $query->limit((int)$options['limit']);
        }

        return $query->get()->map(function ($item) {
            return new static($item);
        })->toArray();
    }

    /**
     * @param array $options
     * @param int $perPage
     *
     * @return LengthAwarePaginatorContract
     * @throws \Exception
     */
    public function getAllPaginator(array $options = [], int $perPage = 15): LengthAwarePaginatorContract
    {
        if ((int)$perPage > 0) {
            $perPage = (int)$perPage;
        } else {
            $perPage = 15;
        }

        $paginator = $this->getAllQuery($options)->paginate($perPage);

        return $paginator->setCollection(new Collection(array_map(fn (Model $model) : RepositoryInterface => new static($model), $paginator->items())));
    }

    /**
     * @param array $options
     * @param int $offset
     * @param int $perPage
     *
     * @return LengthAwarePaginatorContract
     * @throws \Exception
     */
    public function getAllDynamicPaginator(array $options = [], $offset = 0, $perPage = 15): LengthAwarePaginatorContract
    {
        if ((int)$perPage > 0) {
            $perPage = (int)$perPage;
        } else {
            $perPage = 15;
        }

        $query = $this->getAllQuery($options);

        $total = $query->toBase()->getCountForPagination();

        $results = ($total > 0) ? $query->offset($offset)->take($perPage)->get() : $query->getModel()->newCollection();

        $paginator = new LengthAwarePaginator($results, $total, $perPage);

        return $paginator->setCollection(new Collection(array_map(fn (Model $model) : RepositoryInterface => new static($model), $paginator->items())));
    }

    /**
     * @param array $conditions
     * @param array $options
     *
     * @return RepositoryInterface|null
     */
    public function getOne(array $conditions = [], array $options = [])
    {
        $queryOptions = [];

        if (count($conditions) > 0) {
            $queryOptions = [
                'conditions' => $conditions,
            ];
        }

        try {

            return new static($this->getAllQuery($queryOptions + $options)->firstOrFail());

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param array $conditions
     * @param array $options
     *
     * @return RepositoryInterface[]|null
     */
    public function getRandom(array $conditions = [], array $options = [])
    {
        $queryOptions = [];

        if (count($conditions) > 0) {
            $queryOptions = [
                'conditions' => $conditions,
            ];
        }

        try {

            $query = $this->getAllQuery($options);

            if (array_key_exists('limit', $options) && is_numeric($options['limit'])) {
                $query->limit($options['limit']);
            }

            return $this->getAllQuery($queryOptions + $options)->inRandomOrder()->get()->map(function ($item) {
                return new static($item);
            })->toArray();

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param array $conditions
     * @param array $options
     *
     * @return int
     *
     * @throws \Exception
     */
    public function count(array $conditions = [], array $options = []): int
    {
        return $this->getAllQuery([
            'conditions' => $conditions,
        ])->count();
    }


    /**
     * @param array $data
     * @param callable|null $successCallback
     * @param callable|null $failCallback
     *
     * @return bool
     *
     * @throws ModelSaveException
     */
    public function update(array $data, ?callable $successCallback = null, ?callable $failCallback = null): bool
    {
        return $this->handleModification($data, $successCallback, $failCallback);
    }

    /**
     * @param string $field
     *
     * @return bool
     * @throws \Exception
     */
    public function switch(string $field): bool
    {
        if (in_array($field, $this->switchableFields)) {
            return $this->update([
                $field => !$this->getModel()->{$field},
            ]);
        } else {
            throw new \Exception('Switching ' . $field . ' is not allowed for ' . static::class);
        }
    }

    /**
     * @return bool
     */
    public function touch(): bool
    {
        return $this->getModel()->touch();
    }

    /**
     * @param array|null $relations
     * @return RepositoryInterface
     */
    public function copy(?array $relations = null)
    {
        $newModel = $this->getModel()->replicate();

        $newModel->push();

        if ($relations !== null) {
            $this->getModel()->relations = [];
            $this->getModel()->load($relations);
        }

        $thisRelations = $this->getModel()->getRelations();

        if (is_array($thisRelations)) {
            foreach ($thisRelations as $relationName => $values) {
                $newModel->{$relationName}()->sync($values);
            }
        }

        return (new static($newModel));
    }

    /**
     * @param bool $soft
     *
     * @return bool
     * @throws ModelDeleteException
     */
    public function delete(bool $soft = false): bool
    {
        try {
            return $this->getModel()->delete();
        } catch (\Exception $e) {
            throw new ModelDeleteException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @param array $items
     * @param array|null $fields
     *
     * @return array
     */
    public function mapList(array $items, ?array $fields = null): array
    {
        $result = [];

        foreach ($items AS $item) {
            $result[] = ((($item instanceof static) ? $item : new static($item)))->mapDetail($fields);
        }

        return $result;
    }

    /**
     * @return array
     */
    abstract protected function getDataStructure(): array;

    /**
     * @param array|null $fields
     *
     * @return array
     */
    public function mapDetail(?array $fields = null): array
    {
        $dataStructure = $this->getDataStructure();

        return array_map(function ($v) use ($fields) {
            return (($v instanceof Closure) ? $v($fields) : $v);
        }, ((is_array($fields)) ? array_filter($dataStructure, function ($v, $k) use ($fields) {
            return in_array($k, $fields);
        }, ARRAY_FILTER_USE_BOTH) : $dataStructure));
    }
}
