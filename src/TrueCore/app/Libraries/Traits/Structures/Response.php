<?php

namespace TrueCore\App\Libraries\Traits\Structures;

use TrueCore\App\Libraries\Structure\Structure;
use TrueCore\App\Libraries\Loggers\RequestLogger;
use TrueCore\App\Libraries\Traits\Loggable;

/**
 * Class Response
 *
 * @package App\Libraries\Traits\Structures
 */
class Response extends Structure
{
    use Loggable;

    protected ResponseRequest         $request;
    protected                         $response;
    protected ResponseStats           $stats;

    /**
     * Response constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        if ( $this->getStats()->getRequestDuration() > 1.0 ) {

            $this->initLogEvents();

            $this->setLogData(RequestLogger::class, 'requestWarning', [
                'warning' => array_merge($this->getRequest()->toArray(), ['requestDuration' => $this->getStats()->getRequestDuration()]),
            ]);
        }
    }

    /**
     * @return ResponseRequest
     */
    public function getRequest(): ResponseRequest
    {
        return $this->request;
    }

    /**
     * @return mixed|string|array
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return ResponseStats
     */
    public function getStats(): ResponseStats
    {
        return $this->stats;
    }

    /**
     * @param array $array
     */
    public function setStats(array $array): void
    {
        $this->stats = new ResponseStats($array);
    }

    /**
     * @param array $array
     */
    public function setRequest(array $array): void
    {
        $this->request = new ResponseRequest($array);
    }

    /**
     * @return array
     */
    protected function validationRules(): array
    {
        return [];
    }
}
