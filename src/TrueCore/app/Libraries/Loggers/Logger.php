<?php

namespace TrueCore\App\Libraries\Loggers;

use TrueCore\App\Libraries\Structure\Structure;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Log\LogManager;

/**
 * Class Logger
 *
 * @property array          $allowedLogLevels
 * @property Structure|null $data
 * @property string         $logChannel
 * @property string         $logLevel
 * @property LogManager     $logManager
 *
 * @package App\Libraries\Loggers
 */
abstract class Logger implements LoggerInterface
{
    protected array $allowedLogLevels = ['info', 'notice', 'error', 'warning', 'critical'];

    protected ?Structure                                       $data;
    protected string                                           $logChannel;
    protected string                                           $logLevel;
    protected LogManager                                       $logManager;

    /**
     * Logger constructor.
     *
     * @param string         $logChannel
     * @param string         $logLevel
     * @param Container|null $container
     */
    public function __construct(string $logChannel, string $logLevel, ?Container $container = null)
    {
        if ( in_array($logLevel, $this->allowedLogLevels, true) === false ) {
            throw new \InvalidArgumentException('Invalid log level. Allowed: ' . implode(',', $this->allowedLogLevels));
        }

        $this->logChannel = $logChannel;

        $this->logLevel = $logLevel;

        /** @var Application $container */
        if ( $container === null ) {
            $container = Container::getInstance();
        }

        $this->logManager = new LogManager($container);
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogManager(): \Psr\Log\LoggerInterface
    {
        return $this->logManager->channel($this->logChannel);
    }
}
