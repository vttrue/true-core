<?php

namespace TrueCore\App\Libraries\Exceptions;

use Throwable;

/**
 * Class RequestException
 *
 * @package App\Libraries\Exchange\Exceptions
 */
class RequestException extends \Exception
{
    protected string $requestUrl     = '';
    protected        $requestData    = [];
    protected array  $requestHeaders = [];
    protected array  $error          = [];

    /**
     * RequestException constructor.
     *
     * @param string         $message
     * @param int            $code
     * @param null|Throwable $previous
     * @param string         $requestUrl
     * @param array          $requestData
     * @param array          $requestHeaders
     * @param array          $error
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null, string $requestUrl = '', $requestData = [], array $requestHeaders = [], array $error = [])
    {
        parent::__construct($message, $code, $previous);

        $this->requestUrl = $requestUrl;
        $this->requestData = $requestData;
        $this->requestHeaders = $requestHeaders;
        $this->error = $error;
    }

    /**
     * @return string
     */
    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    /**
     * @return array|string
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * @return array
     */
    public function getRequestHeaders(): array
    {
        return $this->requestHeaders;
    }

    /**
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }
}
