<?php

namespace TrueCore\App\Console\Commands;

use \TrueCore\App\Models\System\Entity;
use \TrueCore\App\Models\Traits\{
    HasImageFields,
    HasImages
};
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EnsureImageFieldDimensions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'image:ensure-dimensions {--entity=} {{--entityId=}}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensures that all image field width and height values are correctly set.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $targetEntity   = $this->option('entity');
        $targetEntityId = $this->option('entityId');

        $entityListQuery = Entity::query();

        if($targetEntity !== null) {
            $entityListQuery->where('namespace', '=', $targetEntity);
        }

        $entityList = $entityListQuery->get()->toArray();

        foreach($entityList AS $entity) {

            $traits         = class_uses($entity['namespace']);
            $hasImages      = in_array(HasImages::class, $traits);
            $hasImageFields = in_array(HasImageFields::class, $traits);

            if($hasImageFields || $hasImages) {

                $entityRecordsQuery = new $entity['namespace'];

                if($targetEntityId !== null) {
                    $entityRecordsQuery = $entityRecordsQuery->where($entityRecordsQuery->getKeyName(), '=', $targetEntityId);
                }

                $entityRecords = $entityRecordsQuery->get();

                foreach ($entityRecords AS $record) {

                    $this->processEntity($record);

                }

            }

        }

        return 0;
    }

    /**
     * @param Model $model
     *
     * @return array
     */
    protected function assignDimensions(Model $model) : array
    {
        $result = [];

        if(method_exists($model, 'getImageFields')) {

            $shouldSave = false;

            $imageFields = $model->getImageFields();

            if (is_array($imageFields) && count($imageFields) > 0) {

                foreach ($imageFields AS $imageField) {

                    if (is_string($model->{$imageField}) && Storage::disk('image')->exists($model->{$imageField})) {

                        [$width, $height] = getimagesize(Storage::disk('image')->path($model->{$imageField}));

                        $model->{(($imageField !== 'file_path') ? $imageField . '_width' : 'width')} = $width;
                        $model->{(($imageField !== 'file_path') ? $imageField . '_height' : 'height')} = $height;

                        $shouldSave = true;

                        $result[] = $imageField . ': ' . $width . 'x' . $height;
                    }

                }

                if ($shouldSave === true) {
                    $model->save();
                }

            }

        }

        return $result;
    }

    /**
     * @param Model $modelRecord
     */
    protected function processEntity(Model $modelRecord)
    {
        $traits         = class_uses(get_class($modelRecord));
        $hasImages      = in_array(HasImages::class, $traits);
        $hasImageFields = in_array(HasImageFields::class, $traits);

        if($hasImageFields || $hasImages) {

            //$entityRecords = $modelRecord->newQuery()->get();
            $record = $modelRecord;

            //foreach ($entityRecords AS $record) {

                if ($hasImageFields) {

                    $result = $this->assignDimensions($record);

                    if(count($result) > 0) {
                        echo 'Entity ' . get_class($record) . ' [' . $record->getKeyName() . ': ' . $record->getKey() . '] has been processed : ' . implode("\n", $result) . "\n";
                    }

                }

                if ($hasImages) {

                    $recordImages = $record->images;

                    foreach($recordImages AS $image) {

                        $result = $this->assignDimensions($image);

                        if(count($result) > 0) {
                            echo 'Entity ' . get_class($image) . ' [' . $image->getKeyName() . ': ' . $image->getKey() . '] has been processed : ' . implode("\n", $result) . "\n";
                        }

                    }

                }

                if(method_exists($record, 'getImageEntityRelations')) {
                    $relatedImageEntityRelations = $record->getImageEntityRelations();

                    foreach ($relatedImageEntityRelations AS $relatedImageEntityClassName => $relatedImageEntityRelationName) {
                        if (method_exists($record, $relatedImageEntityRelationName)) {
                            $record->{$relatedImageEntityRelationName}->each(function ($relatedImageEntity) {
                                $this->processEntity($relatedImageEntity);
                            });
                        }
                    }
                }

            }

        //}
    }
}
