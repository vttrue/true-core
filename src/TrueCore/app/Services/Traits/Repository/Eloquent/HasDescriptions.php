<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 12.02.2020
 * Time: 23:50
 */

namespace TrueCore\App\Services\Traits\Repository\Eloquent;

use Illuminate\Database\Eloquent\Model;
use \TrueCore\App\Models\Traits\HasDescriptions as ModelHasDescriptions;
use Illuminate\Support\Str;

use \Closure;

/**
 * Trait HasDescriptions
 *
 * @property array $descriptionFields
 *
 * @package TrueCore\App\Services\Traits\Repository\Eloquent
 */
trait HasDescriptions
{
    /**
     * @return array
     */
    protected function getDescriptionFields() : array
    {
        return ((property_exists($this, 'descriptionFields')) ? $this->descriptionFields : []);
    }

    /**
     * @param Model $model
     * @param array $descriptionList
     * @param Closure|null $afterSaveCallback | Arguments: Model $descriptionModel, array $description
     *
     * @throws \Exception
     */
    protected function saveDescriptions(Model $model, array $descriptionList, ?Closure $afterSaveCallback = null) : void
    {
        $fullClassName  = get_class($model);
        $classUses      = class_uses($model);

        if(in_array(ModelHasDescriptions::class, $classUses) === false) {
            throw new \Exception('Model ' . $fullClassName . ' does not have any Descriptions');
        }

        $descriptionFields = $this->getDescriptionFields();

        if(count($descriptionFields) === 0) {
            throw new \Exception('$descriptionFields property of class ' . static::class . ' must be set correctly');
        }

        $classNames     = class_parents($model);
        array_unshift($classNames, $fullClassName);

        $entityFields = [];

        foreach($classNames AS $className) {
            if(array_key_exists($className, $descriptionFields) && is_array($descriptionFields[$className])) {
                $entityFields = $descriptionFields[$className];
                break;
            }
        }

        if(count($entityFields) === 0) {
            throw new \Exception('A Description entity must have at least one field');
        }

        foreach($descriptionList AS $description) {

            $fields = array_map(function ($field) use($description) {
                return (($field instanceof \Closure) ? $field($description) : $description[$field]);
            }, array_filter($entityFields, function ($value, $field) use($description) {
                return (array_key_exists(Str::camel($field), $description) && (($value instanceof \Closure) || is_string($value)));
            }, ARRAY_FILTER_USE_BOTH));

//            $fields = array_combine(array_map(function ($field) {
//                return Str::snake($field);
//            }, array_keys($fields)), $fields);

            $descriptionModel = null;

            if (is_array($description) && array_key_exists('id', $description) && is_numeric($description['id'])) {
                $descriptionModel = $model->descriptions()->find($description['id']);
            }

            if ($descriptionModel !== null) {
                $descriptionModel = $descriptionModel->fill($fields);

                $this->saveModel($descriptionModel);

            } else {

                $descriptionModel = $model->description;

                if ($descriptionModel === null) {
                    $descriptionModel = $model->descriptions()->newModelInstance([
                        $model->getForeignKey() => $model->id,
                    ])->fill($fields);
                } else {
                    $descriptionModel->fill($fields);
                }

                $this->saveModel($descriptionModel);
            }

            // If we need to perform any other manipulations
            if ($afterSaveCallback instanceof Closure) {
                $afterSaveCallback($descriptionModel, $description);
            }
        }
    }
}