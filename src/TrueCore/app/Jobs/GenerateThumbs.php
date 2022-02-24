<?php

namespace TrueCore\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\{
    Model,
    ModelNotFoundException
};
use Illuminate\Queue\{
    InteractsWithQueue,
    SerializesModels
};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Class GenerateThumbs
 *
 * @package TrueCore\App\Jobs
 */
class GenerateThumbs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels {
        restoreModel as originalRestoreModel;
    }

    private Model $_model;
    private ?int  $_scheduledAt = null;
    private bool  $_force       = false;

    /**
     * Create a new job instance.
     *
     * @param Model $model
     * @param int|null $scheduledAt
     * @param bool $force
     *
     * @return void
     */
    public function __construct(Model $model, ?int $scheduledAt = null, bool $force = false)
    {
        $this->queue        = 'generate_thumbs';

        $this->_model       = $model;
        $this->_scheduledAt = $scheduledAt;
        $this->_force       = $force;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(method_exists($this->_model, 'generateThumbs')) {
            $this->_model->generateThumbs(false, $this->_force);
        }
    }

    /**
     * Restore the model from the model identifier instance.
     *
     * @param  \Illuminate\Contracts\Database\ModelIdentifier  $value
     * @return Model
     */
    public function restoreModel($value)
    {
        try {

            return $this->originalRestoreModel($value);

        } catch(ModelNotFoundException $e) {

            // If unable to fetch the model in a minute after the queue task has been dispatched, we simply requeue the task in case a transaction is not yet committed
            // We dispatch it with a one second delay. Obviously we don't want queue jobs to spawn that rapidly.
            if((time() - $this->_scheduledAt) < 60) {

                $this->delete();

                /** @var Model $model */
                $model = new $value->class;
                $model->{$model->getKeyName()} = $value->{$model->getKeyName()};

                $this::dispatch($model, $this->_scheduledAt)->delay(1);

                return null;

            } else {
                throw new ModelNotFoundException($e->getMessage(), $e->getCode(), $e);
            }

        }
    }
}
