<?php

namespace TrueCore\App\Libraries\Loggers;

use TrueCore\App\Libraries\Loggers\Structures\RequestData;
use Illuminate\Container\Container;
use Illuminate\Log\LogManager;

/**
 * Class RequestLogger
 *
 * @property array            $allowedLogLevels
 * @property RequestData|null $data
 * @property string           $logChannel
 * @property string           $logLevel
 * @property LogManager       $logManager
 *
 * @package App\Libraries\Loggers
 */
class RequestLogger extends Logger
{
    /**
     * RequestLogger constructor.
     *
     * @param string         $logChannel
     * @param string         $logLevel
     * @param array          $data
     * @param Container|null $container
     */
    public function __construct(string $logChannel, string $logLevel, array $data = [], ?Container $container = null)
    {
        parent::__construct($logChannel, $logLevel, $container);

        $this->data = ((count($data) > 0) ? new RequestData($data) : null);
    }

    /**
     * @throws \JsonException
     */
    public function handle(): void
    {
        $this->getLogManager()->{$this->logLevel}($this->getLogText());
    }

    /**
     * @return string
     *
     * @throws \JsonException
     */
    private function getLogText(): string
    {
        if ( $this->data instanceof RequestData === false ) {
            return '';
        }

        return "\n" . 'Url: ' . $this->data->getRequestUrl() . "\n"
               . 'MethodType: ' . $this->data->getRequestType() . "\n"
               . 'Data: ' .
               ((is_array($this->data->getRequestData())) ? json_encode($this->data->getRequestData(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) :
                   $this->data->getRequestData()) . "\n"
               . 'Headers: ' . json_encode($this->data->getRequestHeaders(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n"
               . 'Request duration: ' . $this->data->getRequestDuration();
    }
}
