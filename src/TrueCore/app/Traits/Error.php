<?php

namespace TrueCore\App\Traits;

trait Error
{
    /**
     * @param \Throwable $exceptionInstance
     * @return array
     */
    public static function getDebugData(\Throwable $exceptionInstance): array
    {
        return [
            'File'          => $exceptionInstance->getFile(),
            'Line'          => $exceptionInstance->getLine(),
            'ExceptionType' => get_class($exceptionInstance),
            'Trace'         => $exceptionInstance->getTrace(),
        ];
    }
}
