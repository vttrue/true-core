<?php


namespace TrueCore\App\Libraries\Payment\Handlers;

use TrueCore\App\Libraries\Traits\Restful;

class CreditEuropeHandler extends BasePaymentHandler
{
    use Restful;

    protected $testUrl           = 'https://web.rbsuat.com/ab/rest/';
    protected $baseUrl           = 'https://payment.crediteurope.ru/payment/rest/';

    public $user;
    public $password;

    protected $currencyList = [
        840 => 'USD',
        980 => 'UAH',
        810 => 'RUB',
        946 => 'RON',
        398 => 'KZT',
        417 => 'KGS',
        392 => 'JPY',
        826 => 'GBR',
        978 => 'EUR',
        156 => 'CNY',
        974 => 'BYR',
    ];

    public function registerOrder(): array
    {
        $order = $this->getOrder();
        $order = $order->getOrderData();

        $data = [
            'userName' => $this->user,
            'password' => "!api%crcubRap234",
            'orderNumbe' => $order['orderData']['id'],
            'amount' => $order['amount'],
            'currency' => array_search('RUB', $this->currencyList),
            'returnUrl' => $order['returnUrl'],
            'failUrl' => $order['failUrl'],
            'clientId' => $order['orderData']['customer']['id'],
            'email' => $order['orderData']['email'],
            'phone' => ($order['orderData']['phone'][0] !== '+') ? '+'.$order['orderData']['phone']: $order['orderData']['phone'],
        ];

        $data = http_build_query($data);

        try {
            $response = $this->makeRequest($this->getRegisterOrderMethod(), 'POST', $data, $this->getHeaders());
            dd($response);
        } catch (\Throwable $e) {
dd($e);
        }

    }

    public function getOrderStatus(): array
    {

    }

    /**
     * @return string[]
     */
    protected function getHeaders(): array
    {
        return [
            'Content-type' => 'application/x-www-form-urlencoded'
        ];
    }

    /**
     * @return string
     */
    protected function getRegisterOrderMethod(): string
    {
        return $this->baseUrl.'register.do';
    }
}
