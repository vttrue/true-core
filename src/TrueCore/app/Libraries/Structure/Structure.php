<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 2020-04-06
 * Time: 23:40
 */

namespace TrueCore\App\Libraries\Structure;

use TrueCore\App\Libraries\Structure\Exceptions\IncorrectValidationCallbackException;
use TrueCore\App\Libraries\Structure\Exceptions\StructureValidationErrorException;
use TrueCore\App\Libraries\Structure\Exceptions\UnknownStructureParamException;

use \Closure;

use \ReflectionClass;
use \ReflectionProperty;
use \ReflectionException;

/**
 * Class Structure
 *
 * @package TrueCore\App\Libraries
 */
abstract class Structure implements StructureInterface
{
    private static array $_validationErrors = [];

    /**
     * Structure constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $field => $value) {

            $setterName = ucfirst($field);

            if ( method_exists($this, 'set' . $setterName) ) {
                // @TODO: IncorrectStructureParamSetterException | Deprecator @ 2020-04-09
                $this->{'set' . $setterName}($value);
            } else {
                $this->{$field} = $value;
            }

        }

        if ( $this->validate() === false ) {
            throw new StructureValidationErrorException('Errors occurred during ' . static::class . ' validation.', 5051, null, self::$_validationErrors);
        }
    }

    /**
     * @return bool
     */
    protected function validate(): bool
    {
        $errors = [];

        foreach ($this->validationRules() as $field => $callback) {

            if ( ($callback instanceof Closure) === false ) {
                throw new IncorrectValidationCallbackException('Expected \Closure, got ' . ((is_object($callback)) ? get_class($callback) : gettype($callback)));
            }

            try {
                $result = $callback($this->{$field});
            } catch (\Throwable $e) {
                throw new IncorrectValidationCallbackException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }

            if ( !is_array($result) ) {
                throw new IncorrectValidationCallbackException($field . ' validation callback result expected to be an array, ' . ((is_object($result)) ? get_class($result) : gettype($result)) .
                                                               ' returned instead.');
            }

            if ( count($result) > 0 ) {
                $errors[$field] = $result;
            }

        }

        self::$_validationErrors = $errors;

        return (count($errors) === 0);
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    static protected function validateUrl(string $url): bool
    {
        if ( filter_var($url, FILTER_VALIDATE_URL) === false ) {

            $parsedUrl = parse_url($url);

            if ( is_array($parsedUrl) && array_key_exists('scheme', $parsedUrl) && array_key_exists('host', $parsedUrl) && is_string($parsedUrl['scheme']) && $parsedUrl['scheme'] !== '' &&
                 array_key_exists('host', $parsedUrl) && is_string($parsedUrl['host']) && $parsedUrl['host'] !== '' ) {

                $parsedUrl = $parsedUrl['scheme'] . '://' . idn_to_utf8($parsedUrl['host']) .
                             ((array_key_exists('path', $parsedUrl) && is_string($parsedUrl['path']) && $parsedUrl['path'] !== '') ? $parsedUrl['path'] : '') .
                             ((array_key_exists('query', $parsedUrl) && is_string($parsedUrl['query']) && $parsedUrl['query'] !== '') ? '?' . $parsedUrl['query'] : '');

                return ((int) preg_match('/^((https?):\/\/)?[a-zа-я0-9]+([\-\.]{1}[a-zа-я0-9]+)*\.[a-zа-я]{2,5}(:[0-9]{1,5})?(\/.*)?$/u', $parsedUrl) !== 0);
            }

            return false;

        } else {

            return true;
        }
    }

    /**
     * @return array
     */
    abstract protected function validationRules(): array;

    /**
     * @return array
     */
    public function toArray(): array
    {
        $result = [];

        try {

            $propList = (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PROTECTED);

            $mapper = static function(array $value) use (&$mapper) {
                return array_map(static fn($v) => ((is_array($v)) ? $mapper($v) : (($v instanceof self) ? $v->toArray() : $v)), $value);
            };

            foreach ($propList as $prop) {

                if ( $this->{$prop->getName()} === null && $this->preserveNullable() === false ) {
                    continue;
                }

                if ( is_array($this->{$prop->getName()}) ) {
                    $result[$prop->getName()] = $mapper($this->{$prop->getName()});
                } else {
                    $result[$prop->getName()] = (($this->{$prop->getName()} instanceof self) ? $this->{$prop->getName()}->toArray() : $this->{$prop->getName()});
                }
            }

        } catch (ReflectionException $e) {
            // This situation cannot happen in this case, lol
        }

        return $result;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        throw new UnknownStructureParamException('Trying to set an unknown property "' . $name .'". Class: ' . static::class);
    }

    /**
     * @return bool
     */
    protected function preserveNullable(): bool
    {
        return false;
    }
}
