<?php

namespace TrueCore\App\Console\Commands;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use TrueCore\App\Models\System\Entity;
use TrueCore\App\Models\Traits\{
    HasImageFields,
    HasImages
};
use Illuminate\Console\Command;

/**
 * Class GenerateThumbs
 *
 * @package TrueCore\App\Console\Commands
 */
class GenerateThumbs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thumbs:generate {--entity=} {{--entityId=}} {{--force=}}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates thumbnails for entities with images.';

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
     * @return int
     */
    public function handle()
    {
        $targetEntity   = $this->option('entity');
        $targetEntityId = $this->option('entityId');

        $doForce = $this->option('force');
        $doForce = ((is_bool($doForce) && $doForce === true) || (is_string($doForce) && strtolower($doForce) === 'true'));

        $entityListQuery = Entity::query();

        if($targetEntity !== null) {
            $entityListQuery->where('namespace', '=', $targetEntity);
        }

        $entityList = $entityListQuery->get()->toArray();

        $thumbGenerator = function (Model $record, string $serviceClassName) use(&$thumbGenerator, $doForce) {
            try {
                return $record->generateThumbs(false, $doForce, $serviceClassName);
            } catch (\Throwable $e) {

                Log::channel('imageResize')->info([
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'code'      => $e->getCode(),
                    'message'   => $e->getMessage()
                ]);

                return $thumbGenerator($record, $serviceClassName);
            }
        };

        foreach ($entityList AS $entity) {

//            $handlerReflector = new \ReflectionClass();
//
//            if ($handlerReflector->isInstantiable() === false) {
//                throw new \Exception('Cannot instantiate service.');
//            }
//
//            $handlerInstance = $handlerReflector->newInstanceArgs();
//            $handlerInstance::handle();

            $model = (new $entity['namespace'])->getRepository()->getModel();

//            dd($model);

            $traits         = class_uses($model);
            $hasImages      = in_array(HasImages::class, $traits);
            $hasImageFields = in_array(HasImageFields::class, $traits);

            if ($hasImageFields || $hasImages) {

                $entityRecordsQuery = $model;

                if ($targetEntityId !== null) {
                    $entityRecordsQuery = $entityRecordsQuery->where($entityRecordsQuery->getKeyName(), '=', $targetEntityId);
                }

                $entityRecords = $entityRecordsQuery->get();

                foreach ($entityRecords AS $record) {

                    if ($hasImageFields) {

                        $thumbList  = $thumbGenerator($record, $entity['namespace']);
                        $thumbCount = array_reduce(array_column($thumbList, 'thumbList'), static fn($accumulator, $currentValue) : int => ($accumulator + count($currentValue)), 0);

                        if($thumbCount > 0) {
                            echo $thumbCount . ' thumbnails have been generated for entity ' . $entity['namespace'] . ' with ' . $record->getKeyName() . '=' . $record->getKey() . "\n";
                        } else {
                            echo 'Thumbs have been sent for generation for entity ' . $entity['namespace'] . ' with ' . $record->getKeyName() . '=' . $record->getKey() . "\n";
                        }

                    }

                }

            }

        }

        return 0;
    }
}
