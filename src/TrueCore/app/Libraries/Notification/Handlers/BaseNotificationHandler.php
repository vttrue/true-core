<?php

namespace TrueCore\App\Libraries\Notification\Handlers;

/**
 * Class BaseNotificationHandler
 *
 * @package TrueCore\App\Libraries\Notification\Handlers
 */
abstract class BaseNotificationHandler implements NotificationHandlerInterface
{
    /**
     * BaseNotificationHandler constructor.
     *
     * @param array $credentials
     */
    public function __construct(array $credentials)
    {
        if (count($credentials) > 0) {
            foreach ($credentials AS $param => $value) {
                $this->{$param} = $value;
            }
        }
    }

    /**
     * @return array
     */
    protected function getHeaders(): array
    {
        return [];
    }

    /**
     * @param array $to
     * @param string $content
     *
     * @return bool
     */
    abstract public function handle(array $to, string $content): bool;
}
