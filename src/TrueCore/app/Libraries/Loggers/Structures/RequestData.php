<?php

namespace TrueCore\App\Libraries\Loggers\Structures;

use TrueCore\App\Libraries\Structure\Structure;

/**
 * Class RequestData
 *
 * @package App\Libraries\Loggers\Structures
 */
class RequestData extends Structure
{
    protected string   $requestType;
    protected string   $requestUrl;
    protected          $requestData;
    protected array    $requestHeaders;
    protected float    $requestDuration;

    /**
     * @return string
     */
    public function getRequestType(): string
    {
        return $this->requestType;
    }

    /**
     * @return string
     */
    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    /**
     * @return mixed
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
     * @return float
     */
    public function getRequestDuration(): float
    {
        return $this->requestDuration;
    }

    /**
     * @inheritDoc
     */
    protected function validationRules(): array
    {
        return [];
    }
}
