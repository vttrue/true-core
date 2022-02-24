<?php

namespace TrueCore\App\Libraries\Loggers;

use TrueCore\App\Libraries\Traits\Loggable;

/**
 * Class Observer
 *
 * @package App\Libraries\Observers
 */
class Observer
{
    /**
     * @var array
     */
    protected array $data = [];

    /**
     * LogObserver constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

//    public function __call($name, $arguments)
//    {
//        dd($name);
//    }

    /**
     * @param $instance
     *
     * @return void
     */
    public function info($instance): void
    {
        if ( in_array(Loggable::class, class_uses_recursive($instance), true) ) {
            $loggerHandlerClassName = $instance->getLoggerHandler();
            (new $loggerHandlerClassName($instance->getLogChannel(), 'info', $instance->getLogData()['info']))->handle();
        }
    }

    /**
     * @param $instance
     *
     * @return void
     */
    public function warning($instance): void
    {
        if ( in_array(Loggable::class, class_uses_recursive($instance), true) ) {
            $loggerHandlerClassName = $instance->getLoggerHandler();
            (new $loggerHandlerClassName($instance->getLogChannel(), 'warning', $instance->getLogData()['warning']))->handle();
        }
    }
}
