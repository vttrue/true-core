<?php

namespace TrueCore\App\Libraries\Traits\Structures;

use TrueCore\App\Libraries\Structure\Structure;

/**
 * Class ResponseRequest
 *
 * @package App\Libraries\Traits\Structures
 */
class ResponseRequest extends Structure
{
    protected string $requestType;
    protected string $requestUrl;
    protected        $requestData;
    protected array  $requestHeaders;

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
     * @return array|string|mixed
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
    protected function validationRules(): array
    {
        return [];
    }
}
