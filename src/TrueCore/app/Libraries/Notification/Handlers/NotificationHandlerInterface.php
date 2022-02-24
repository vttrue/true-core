<?php

namespace TrueCore\App\Libraries\Notification\Handlers;

/**
 * Interface NotificationHandlerInterface
 *
 * @package TrueCore\App\Libraries\Notification\Handlers
 */
interface NotificationHandlerInterface
{
    /**
     * @param array $to
     * @param string $content
     *
     * @return bool
     */
    public function handle(array $to, string $content): bool;
}
