<?php

namespace TrueCore\App\Libraries\Traits;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use TrueCore\App\Libraries\Loggers\Observer as LogObserver;

/**
 * Trait Loggable
 *
 * @package App\Libraries\Traits
 */
trait Loggable
{
    /**
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * @var array|string[]
     */
    protected array $events = ['info', 'notice', 'error', 'warning', 'critical'];

    /**
     * @var string
     *
     * Logger handler instance
     */
    private string $loggerHandler;

    /**
     * @var string
     */
    private string $logChannel;

    /**
     * @var array
     *
     * Structure example: ['info' => $infoLogData, 'error' => $logData]
     */
    private array $logData = [];

    /**
     * @param string $loggerHandler
     * @param string $logChannel
     * @param array  $logData
     */
    public function setLogData(string $loggerHandler, string $logChannel, array $logData): void
    {
        if ( $this->logData !== $logData ) {

            $this->loggerHandler = $loggerHandler;
            $this->logChannel = $logChannel;
            $this->logData = $logData;

            $logLevels = array_keys($logData);

            foreach ($logLevels as $logLevel) {
                $this->dispatcher->dispatch('log.' . $logLevel . ': ' . static::class, $this);
            }
        }
    }

    /**
     * @return string
     */
    public function getLoggerHandler(): string
    {
        return $this->loggerHandler;
    }

    /**
     * @return string
     */
    public function getLogChannel(): string
    {
        return $this->logChannel;
    }

    /**
     * @return array|array[]
     */
    public function getLogData(): array
    {
        return $this->logData;
    }

    /**
     * @return void
     */
    protected function initLogEvents(): void
    {
        $this->dispatcher = new Dispatcher();

        $data = [];
        $observer = new LogObserver();
        $container = new Container();

        $observerClassName = LogObserver::class;

        $container->bind($observerClassName, function() use ($container, $observer, $data) {
            return new $observer($data);
        });

        foreach ($this->events as $event) {

            if ( method_exists($observer, $event) ) {
                $this->registerServiceEvent($event, $observerClassName . '@' . $event);
            }

            $this->dispatcher->listen('log.' . $event . ': ' . static::class, $observerClassName . '@' . $event);
        }
    }

    /**
     * @param string $event
     * @param string $callback
     *
     * @return void
     */
    protected function registerServiceEvent(string $event, string $callback): void
    {
        if ( array_key_exists($event, $this->events) === false ) {
            $this->events[$event] = [];
        }

        $this->events[$event][$callback] = $callback;
    }
}
