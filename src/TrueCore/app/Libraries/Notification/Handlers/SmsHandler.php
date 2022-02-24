<?php

namespace TrueCore\App\Libraries\Notification\Handlers;

use TrueCore\App\Libraries\Notification\BaseHttpHandler;

/**
 * Class SmsHandler
 *
 * @package TrueCore\App\Libraries\Notification\Handlers
 */
class SmsHandler extends BaseHttpHandler
{
    public string $login    = '';
    public string $password = '';

    /**
     * @param array $to
     * @param string $content
     *
     * @return array
     */
    protected function getData(array $to, string $content): array
    {
        return [
            'login'  => $this->login,
            'psw'    => $this->password,
            'phones' => implode(',', $to),
            'mes'    => $content,
        ];
    }

    /**
     * @param array $to
     * @param string $content
     *
     * @return bool
     * @throws \TrueCore\App\Libraries\Notification\Exceptions\NetworkException
     */
    public function handle(array $to, string $content): bool
    {
        $this->makeRequest($this->getData($to, $content), 'POST', $this->getHeaders());

        return true;
    }
}
