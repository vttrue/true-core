<?php
/**
 * Created by PhpStorm.
 * User: deprecator
 * Date: 21.10.15
 * Time: 11:12
 */

namespace TrueCore\App\Libraries\Payment\Handlers;

use TrueCore\App\Libraries\Payment\Exceptions\NetworkException;
use TrueCore\App\Libraries\Traits\Restful;
use TrueCore\App\Libraries\Exceptions\RequestException;

class AlfaBankHandler extends BasePaymentHandler
{
    use Restful;

    protected $testUrl           = 'https://web.rbsuat.com/ab/rest/';
    protected $baseUrl           = 'https://pay.alfabank.ru/payment/rest/';
    protected $restMethods       = [
        'registerOrder'        => 'register.do',
        'registerOrderPreAuth' => 'registerPreAuth.do',
        'getOrderStatus'       => 'getOrderStatus.do',
        'completeOrder'        => 'deposit.do',
    ];
    protected $currencyList      = [
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
    protected $statusCheckRemote = false;   // Payment status check is performed locally by requesting order status from the payment processor

    public $userName;
    public $password;

    public $preAuth = false;

    private $_orderStatusResponse = [];

    private $excludedOrderDataFields = [
        // 'additional_params',
        'cartRuleHistoryItems',
        'totals',
        'productList',
        'status',
        'statusHistoryItems',
        'payment',
        'delivery',
        'customer',
        'other',
    ];

    /**
     * @return array
     *
     * @throws RequestException
     * @throws \JsonException
     */
    public function registerOrder(): array
    {
        $order = $this->getOrder();
        $order->setMargin($this->margin);

        $orderData = ((is_array($order->order_data)) ?
            array_filter($order->order_data, static fn($innerKey) => ($innerKey !== 'additional_params'), ARRAY_FILTER_USE_KEY)
            : json_decode($order->order_data, true, 512, JSON_THROW_ON_ERROR));

        $orderData['orderData'] = array_filter($orderData['orderData'],
            fn($innerKey) => (in_array($innerKey, $this->excludedOrderDataFields, true) === false),
            ARRAY_FILTER_USE_KEY);

        $jsonData = $orderData;
        unset($jsonData['additional_params']);

        $data = array_merge(
            [
                'userName'           => $this->userName,
                'password'           => $this->password,
                'orderNumber'        => urlencode((($order->getCustomParam('order_id')) ?: $order['code'])),
                'amount'             => urlencode($order['amount']),
                'currency'           => (int) $order['currency'], // RUB
                'description'        => urlencode($order->getCustomParam('description')),
                'jsonParams'         => json_encode($jsonData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'returnUrl'          => (($order->getCustomParam('returnUrl')) ?: /*route('payment.success')*/ ''),
                'failUrl'            => (($order->getCustomParam('failUrl')) ?: /*route('payment.fail')*/ ''),
                'sessionTimeoutSecs' => (($order->getCustomParam('sessionTimeoutSecs')) ?: 1200),
            ],
            ((is_array($order->getCustomParam('additional_params'))) ? $order->getCustomParam('additional_params') : [])
        );

        $url = $this->getRequestUrl() . (($this->preAuth) ? $this->restMethods['registerOrderPreAuth'] : $this->restMethods['registerOrder']);

	$orderResponse = $this->makeRequest($url, 'POST', http_build_query($data), ['Content-Type' => 'application/x-www-form-urlencoded'])->getResponse();

        if ( array_key_exists('errorCode', $orderResponse) ) {
            $orderResponse['error'] = [
                'code'    => $orderResponse['errorCode'],
                'message' => $orderResponse['errorMessage'],
            ];
        } elseif ( array_key_exists('formUrl', $orderResponse) ) {
            $orderResponse['redirectUrl'] = $orderResponse['formUrl'];
        }
        if ( array_key_exists('orderId', $orderResponse) ) {
            $order->setExternalCode($orderResponse['orderId']);
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

        $data = [
            'userName' => $this->userName,
            'password' => $this->password,
            'orderId'  => $order['pp_code'],
        ];

        $responseObj = $this->makeRequest($this->getRequestUrl() . $this->restMethods['getOrderStatus'], 'GET', http_build_query($data));
        $statusResponse = array_merge($responseObj->getResponse(), ['responder_ip' => $responseObj->getStats()->getResponderIp()]);

        $order->setResponse($statusResponse);

        if ( array_key_exists('ErrorCode', $statusResponse) && (int) $statusResponse['ErrorCode'] > 0 && (int) $statusResponse['ErrorCode'] !== 5 ) {

            $statusResponse['error'] = [
                'code'    => $statusResponse['ErrorCode'],
                'message' => $statusResponse['ErrorMessage'],
            ];

        } elseif ( array_key_exists('OrderStatus', $statusResponse) ) {

            //@TODO: we should probably implement some kind of OrderStatusResponse class in order to be able to make additional checks if we need to | deprecator on 18.03.16
            // @TODO: would be cool to implement request collection so we can log it somewhere for debug purpose | deprecator on 18.03.16
            if ( (int) $statusResponse['OrderStatus'] === 1 && count($this->getStatusResponse()) === 0 ) {

                $this->setStatusResponse($statusResponse);

                $completeResponse = $this->makeRequest($this->getRequestUrl() . $this->restMethods['completeOrder'], 'POST', http_build_query(array_merge($data, ['amount' => 0])))->getResponse();
                if ( array_key_exists('errorCode', $completeResponse) && (int) $completeResponse['errorCode'] === 0 ) {
                    return $this->getOrderStatus();
                }

            } elseif ( (int) $statusResponse['OrderStatus'] === 2 ) {

                $statusResponse['success'] = true;
            }
        }

        return $statusResponse;
    }

    /**
     * @return string
     */
    protected function getRequestUrl(): string
    {
        return ((!$this->isTestMode()) ? $this->baseUrl : $this->testUrl);
    }

    /**
     * Sets order status response for further usage. Does not set one if it's been set already.
     *
     * @param array $statusResponse
     */
    private function setStatusResponse(array $statusResponse): void
    {
        if ( count($this->_orderStatusResponse) === 0 ) {
            $this->_orderStatusResponse = $statusResponse;
        }
    }

    /**
     * Gets the old order status response
     *
     * @return array
     */
    private function getStatusResponse(): array
    {
        return $this->_orderStatusResponse;
    }
}
