<?php

namespace TrueCore\App\Libraries\Traits\Structures;

use TrueCore\App\Libraries\Structure\Structure;

/**
 * Class ResponseStats
 *
 * @package App\Libraries\Traits\Structures
 */
class ResponseStats extends Structure
{
    protected float       $requestDuration;
    protected string      $responderIp;

    /**
     * @return float
     */
    public function getRequestDuration(): float
    {
        return $this->requestDuration;
    }

    /**
     * @return string
     */
    public function getResponderIp(): string
    {
        return $this->responderIp;
    }

    /**
     * @return array
     */
    protected function validationRules(): array
    {
        return [];
    }
}
