<?php

namespace TrueCore\App\Libraries\Notification;

use TrueCore\App\Libraries\Notification\Exceptions\NotificationHandlerException;
use TrueCore\App\Libraries\Notification\Handlers\NotificationHandlerInterface;

/**
 * Class Notification
 *
 * @package TrueCore\App\Libraries\Notification
 */
class Notification
{
    const NP_SMS = 1;
    const NP_TELEGRAM = 2;
    const CALL_NOTIFICATION = 3;

    protected array $data = [];

    private ?NotificationHandlerInterface    $_handler             = null;
    private array                            $_remoteResponse      = [];
    public array                             $notificationSettings = [];

    /**
     * Gets a list of available notification handlers
     *
     * @return array
     */
    public static function getNotificationHandlerList()
    {
        return [
            self::NP_SMS => 'TrueCore\App\Libraries\Notification\Handlers\SmsHandler',
            self::NP_TELEGRAM => '\App\Libraries\Notification\Handlers\TelegramHandler',
            self::CALL_NOTIFICATION => '\App\Libraries\CallNotification'
        ];
    }

    /**
     * Notification constructor.
     *
     * @param array $notificationSettings
     */
    public function __construct(array $notificationSettings)
    {
        $this->notificationSettings = $notificationSettings;
    }

    /**
     * @param $notificationProcessor
     *
     * @return NotificationHandlerInterface|null
     * @throws NotificationHandlerException
     */
    protected function getNotificationHandler($notificationProcessor)
    {
        if ($this->_handler === null) {
            $this->setNotificationHandler($notificationProcessor);
        }

        return $this->_handler;
    }

    /**
     * Sets and instantiates a notification handler for chosen notification processor
     *
     * @param string|int $notificationProcessor
     *
     * @throws NotificationHandlerException
     */
    protected function setNotificationHandler($notificationProcessor)
    {
        try {

            if (!array_key_exists($notificationProcessor, self::getNotificationHandlerList()) || (!is_string(self::getNotificationHandlerList()[$notificationProcessor]) || self::getNotificationHandlerList()[$notificationProcessor] === '')) {
                throw new NotificationHandlerException('No payment handler specified'); // no payment processor specified =(
            }

            $npName    = self::getNotificationHandlerList()[$notificationProcessor];
            $reflector = new \ReflectionClass($npName);

            if (!$reflector->isInstantiable()) {
                throw new NotificationHandlerException('Notification Handler could not be instantiated'); // payment handler is not instantiable
            }
        } catch (\Exception $e) {
            throw new NotificationHandlerException('Notification Handler is incorrect -- ' . $e->getMessage()); // payment handler is incorrect
        }

        $instance = $reflector->newInstanceArgs([
            'credentials' => ((array_key_exists($npName, $this->notificationSettings)) ? $this->notificationSettings[$npName] : []),
        ]);

        $this->_handler = $instance;
    }

    /**
     * Send new notification
     *
     * @param integer $notificationProcessor
     * @param array $data
     *
     * @return mixed
     */
    public function send($notificationProcessor, array $data)
    {
        try {

            $handlerInstance = $this->getNotificationHandler($notificationProcessor);

            $to      = ((array_key_exists('to', $data) && is_array($data['to'])) ? array_filter($data['to'], fn($v) => (is_string($v) && $v !== '')) : null);
            $content = ((array_key_exists('content', $data) && is_string($data['content'])) ? $data['content'] : null);

            if ($to === null || $content === null) {
                return new \InvalidArgumentException();
            }

            $response = $handlerInstance->handle($to, $content);

//            $this->saveOrderToSession();
//            if ($this->isResponseSuccessful($response)) {
//                $this->saveOrderToDatabase();
//                $this->checkResult($response);
//            }
//            $this->_handler = null;
        } catch (\Exception $e) {
            //dd($e->getMessage(), $e->getFile(), $e->getLine());
            //self::getLogger()->log('Something went wrong while during order registration: Message: '.$e->getMessage().' | File: '.$e->getFile().' | Line: '.$e->getLine(), Logger::LEVEL_ERROR);
            $response = [];
        }
        return $response;
    }
}
