<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 02.09.2020
 * Time: 22:58
 */

namespace TrueCore\App\Libraries\ImageResizeManager\Exceptions;

use Throwable;

/**
 * Class ApiResponseException
 *
 * @package App\Libraries\ImageResizeManager
 */
class ApiResponseException extends \Exception
{
    protected int $statusCode = 0;

    /**
     * ApiResponseException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param int $statusCode
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null, int $statusCode = 0)
    {
        $this->statusCode = $statusCode;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int
     */
    public function getStatusCode() : int
    {
        return $this->statusCode;
    }
}