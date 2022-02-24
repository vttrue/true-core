<?php

namespace TrueCore\App\Libraries\Payment\Handlers;

use Illuminate\Support\Str;
use TrueCore\App\Libraries\Traits\Restful;
use TrueCore\App\Libraries\Exceptions\RequestException;

/**
 * Class YandexKassaHandler
 *
 * @package TrueCore\App\Libraries\Payment\Handlers
 */
class YandexKassaHandler extends BasePaymentHandler
{
    use Restful;

    public string $shopId;
    public string $shopPassword;
    public string $securityType = 'MD5';

    protected $baseUrl = 'https://payment.yandex.net/api/v3/';

    protected $restMethods = [
        'registerOrder'  => 'payments',
        'getOrderStatus' => 'payments',
    ];

    protected $currencyList      = [
        643 => 'RUB',
    ];
    protected $statusCheckRemote = true;

    /**
     * @return array
     *
     * @throws \JsonException
     * @throws RequestException
     */
    public function registerOrder(): array
    {
        $order = $this->getOrder();
        $orderData = $order->getOrderData();

        /** @TODO: не дёргать конфиг. Incarnator | 2020-05-05 */
        $data = [
            'id'           => $orderData['orderData']['id'],
            'orderData'    => $orderData['orderData'],
            'amount'       => [
                "value"    => (float) $orderData['amount'],
                "currency" => "RUB",
            ],
            "capture"      => true,
            "confirmation" => [
                "type"       => "redirect",
                "return_url" => $orderData['returnUrl'] ?? config('app.frontUrl'),
            ],
            "description"  => 'Заказ №' . $orderData['orderData']['id'] . ' на сайте ' . config('app.frontUrl'),
        ];

        foreach ($orderData['totals'] as $total) {
            if ($total['code'] === 'rule_order_discount_ApplyOrderDiscount') {

            }
        }

        $this->getOrder()->setOrderData($data);

        $headers = array_merge($this->getRequestHeaders(), ['Idempotence-Key' => Str::uuid()->toString()]);

        $orderResponse = $this->makeRequest($this->baseUrl . $this->restMethods['registerOrder'], 'POST', $data, $headers)->getResponse();

        if ( array_key_exists('confirmation', $orderResponse) && is_array($orderResponse['confirmation'])
             && array_key_exists('confirmation_url', $orderResponse['confirmation']) && is_string($orderResponse['confirmation']['confirmation_url']) ) {
            $orderResponse['redirectUrl'] = $orderResponse['confirmation']['confirmation_url'];
        } else {
            $orderResponse['error'] = true;
        }

        if ( array_key_exists('id', $orderResponse) ) {
            $order->setExternalCode($orderResponse['id']);
            $orderResponse['success'] = true;
        }

        return $orderResponse;
    }

    /**
     * @return array
     *
     * @throws RequestException
     * @throws \JsonException
     */
    public function getOrderStatus(): array
    {
        $order = $this->getOrder();

        $headers = $this->getRequestHeaders();

        $url = $this->baseUrl .$this->restMethods['getOrderStatus'] . '/' . $order->pp_code;

        $responseObj = $this->makeRequest($url, 'GET', [], $headers);
        $statusResponse = array_merge($responseObj->getResponse(), ['responder_ip' => $responseObj->getStats()->getResponderIp()]);

        $order->setResponse($statusResponse);

        if ( array_key_exists('status', $statusResponse) && is_string($statusResponse['status']) && $statusResponse['status'] === 'canceled' ) {

            $message = ((array_key_exists('cancellation_details', $statusResponse) && is_array($statusResponse['cancellation_details'])
                         && array_key_exists('reason', $statusResponse['cancellation_details']) && is_string($statusResponse['cancellation_details']['reason']))
                ? $statusResponse['cancellation_details'] : 'Unknown error');

            $statusResponse['error'] = [
                'message' => $message,
            ];

        } elseif ( array_key_exists('paid', $statusResponse) && is_bool($statusResponse['paid']) && $statusResponse['paid'] === true ) {

            $statusResponse['success'] = true;
        }

        return $statusResponse;
    }

    /**
     * @return array
     */
    protected function getRequestHeaders(): array
    {
        $headers = [];

        if ( $this->shopId && $this->shopPassword ) {
            $encodedAuth = base64_encode($this->shopId . ':' . $this->shopPassword);
            $headers['Authorization'] = 'Basic ' . $encodedAuth;
        }

        $headers['Content-Type'] = 'application/json';

        return $headers;
    }

    protected function discountForUseProduct(array $orderData)
    {
        $firstTotalPrice = $orderData['totals'][0]['value'];

        if ($firstTotalPrice > $orderData['totalPrice']) {
            $total = $firstTotalPrice - $totalPrice;

            $discount = $total / array_reduce(function ($curry, $item) {
                    return $curry + $item['quantity'];
                }, $orderData['productList'], 0);

            if (is_float($discount) === true) {
                $discount = round($discount, 2);
            }

            return $discount;
        }

        return 0;
    }
}
