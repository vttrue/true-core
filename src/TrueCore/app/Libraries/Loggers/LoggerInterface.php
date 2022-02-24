<?php

namespace TrueCore\App\Libraries\Loggers;

/**
 * Interface LoggerInterface
 *
 * @package App\Libraries\Loggers
 */
interface LoggerInterface
{
    /**
     * @throws \JsonException
     */
    public function handle(): void;
}
