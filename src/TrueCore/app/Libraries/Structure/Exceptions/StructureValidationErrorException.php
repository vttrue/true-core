<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 2020-04-08
 * Time: 19:40
 */

namespace TrueCore\App\Libraries\Structure\Exceptions;

use \InvalidArgumentException;
use \Throwable;

/**
 * Class StructureValidationErrorException
 *
 * @package App\Libraries\Exchange\Exceptions
 */
class StructureValidationErrorException extends InvalidArgumentException
{
    protected array $errors = [];

    /**
     * StructureValidationErrorException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param array $errors
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null, array $errors = [])
    {
        parent::__construct($message, $code, $previous);

        $this->errors = $errors;
    }

    /**
     * @return array
     */
    public function getErrors() : array
    {
        return $this->errors;
    }
}
