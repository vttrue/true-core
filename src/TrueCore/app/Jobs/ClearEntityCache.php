<?php

namespace TrueCore\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ClearEntityCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels {
        restoreModel as originalRestoreModel;
    }

    private $_model                 = null;
    private $_eventName             = '';
    private $_history               = [];
    private $_wasRecentlyCreated    = false;
    private $_scheduledAt           = null;

    /**
     * Create a new job instance.
     *
     * @param Model $model
     * @param string $eventName
     * @param array $history
     * @param bool $wasRecentlyCreated
     * @param int|null $scheduledAt
     *
     * @return void
     */
    public function __construct(Model $model, string $eventName, array $history, bool $wasRecentlyCreated = false, $scheduledAt = null)
    {
        $this->_model = $model;
        $this->queue = 'entity_invalidation';
        $this->_eventName = $eventName;
        $this->_history = $history;
        $this->_wasRecentlyCreated = $wasRecentlyCreated;
        $this->_scheduledAt = $scheduledAt;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(in_array($this->_eventName, ['saved', 'deleting'], true) && method_exists($this->_model, 'invalidate')) {
            try {
                $this->_model->invalidate($this->_eventName, $this->_history, $this->_wasRecentlyCreated);
            } catch(\Throwable $e) {
                dd($e->getMessage());
            }
        }
    }

    /**
     * Restore the model from the model identifier instance.
     *
     * @param  \Illuminate\Contracts\Database\ModelIdentifier  $value
     * @return \Illuminate\Database\Eloquent\Model
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

                /** @var \Illuminate\Database\Eloquent\Model $model */
                $model = new $value->class;
                $model->{$model->getKeyName()} = $value->id;

                $this::dispatch($model, $this->_eventName, $this->_history, $this->_wasRecentlyCreated, $this->_scheduledAt)->delay(1);

                return null;

            } else {
                throw new ModelNotFoundException($e->getMessage(), $e->getCode(), $e);
            }

        }
    }
}
