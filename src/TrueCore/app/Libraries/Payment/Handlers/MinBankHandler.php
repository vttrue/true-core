<?php

namespace TrueCore\App\Libraries\Payment\Handlers;

use TrueCore\App\Libraries\Payment\Exceptions\{
    GetOrderStatusException,
    RegisterOrderException
};
use TrueCore\App\Libraries\Payment\Models\PaymentOrder;
use TrueCore\App\Libraries\Traits\{
    Restful,
    Structures\Response
};
use TrueCore\App\Libraries\Exceptions\RequestException;
use JsonException;

/**
 * Class MinBankHandler
 *
 * @property string $apiUrl
 * @property array  $restMethods
 * @property array  $currencyList
 * @property array  $registerOrderStatusList
 * @property array  $getOrderStatusList
 *
 * @package TrueCore\App\Libraries\Payment\Handlers
 */
class MinBankHandler extends BasePaymentHandler
{
    use Restful;

    public string $merchant;

    protected string $apiUrl = 'https://mpit.minbank.ru:443/';

    protected $restMethods = [
        'registerOrder'  => 'Exec',
        'getOrderStatus' => 'Exec',
    ];

    protected $currencyList = [
        643 => 'RUB',
        840 => 'USD',
    ];

    protected array $registerOrderStatusList = [
        '00' => 'Success',
        '30' => 'Bad request',
        '10' => 'Forbidden: access denied',
        '54' => 'Illegal operation',
        '96' => 'System error',
    ];

    protected array $getOrderStatusList = [
        'APPROVED' => 'Payment success',
        'CANCELED' => 'Canceled payment',
        'DECLINED' => 'Declined payment',
        'EXPIRED'  => 'Expired payment',
    ];

    protected const  ORDER_EXPIRATION_PERIOD       = 20; // In minutes
    protected const  REGISTER_ORDER_SUCCESS        = '00';
    protected const  GET_ORDER_STATUS_SUCCESS_LIST = ['APPROVED'];
    protected const  GET_ORDER_STATUS_FAILED_LIST  = ['CANCELED', 'DECLINED', 'EXPIRED'];

    /**
     * @return array
     *
     * @throws JsonException
     * @throws RegisterOrderException
     * @throws RequestException
     */
    public function registerOrder(): array
    {
        $order = $this->getOrder();
        $order->setMargin($this->margin);

        $data = $this->buildXML($order, 'registerOrder');

        $responseObj = $this->makeRequest($this->apiUrl . $this->restMethods['registerOrder'], 'POST', $data, ['Content-Type' => 'text/xml'], false);

        $response = $this->prepareRegisterOrderResponseData($responseObj);

        if ( array_key_exists('success', $response) && $response['success'] === true ) {
            $order->setExternalCode($response['Response']['Order']['OrderID'] . '--' . $response['Response']['Order']['SessionID']);
        }

        return $response;
    }

    /**
     * @return array
     *
     * @throws GetOrderStatusException
     * @throws JsonException
     * @throws RequestException
     */
    public function getOrderStatus(): array
    {
        $order = $this->getOrder();

        $data = $this->buildXML($this->getOrder(), 'getOrderStatus');

        $responseObj = $this->makeRequest($this->apiUrl . $this->restMethods['getOrderStatus'], 'POST', $data, ['Content-Type' => 'text/xml'], false);

        $statusResponse = $this->prepareGetOrderStatusResponseData($responseObj);

        $order->setResponse($statusResponse);

        return $statusResponse;
    }

    /**
     * Building XML response.
     *
     * @param PaymentOrder $order
     * @param string       $type
     *
     * @return string
     */
    protected function buildXML(PaymentOrder $order, string $type): string
    {
        if ( array_key_exists($type, $this->restMethods) === false ) {
            throw new \InvalidArgumentException('Invalid type. Allowed: ' . implode(', ', array_keys($this->restMethods)));
        }

        if ( $type === 'registerOrder' ) {

            $orderData = $order->getOrderData()['orderData'];

            $currencyIndex = array_search('RUB', $this->getCurrencyList(), true);

            $description = 'Заказ №' . $orderData['id'];

            return '<?xml version="1.0" encoding="UTF-8"?>'
                   . '<TKKPG>'
                   . '<Request>'
                   . '<Operation>CreateOrder</Operation>'
                   . '<Language>RU</Language>'
                   . '<Order>'
                   . '<OrderType>Purchase</OrderType>'
                   . '<Merchant>' . $this->merchant . '</Merchant>'
                   . '<Amount>' . ($orderData['totalPrice'] * 100) . '</Amount>'
                   . '<Currency>' . $currencyIndex . '</Currency>'
                   . '<Description>' . $description . '</Description>'
                   . '<email>' . ($orderData['email'] ?? '') . '</email>'
                   . '<phone>' . ($orderData['phone'] ?? '') . '</phone>'
                   . '<ApproveURL>' . config('app.frontUrl') . '</ApproveURL>'
                   . '<CancelURL>' . config('app.frontUrl') . '</CancelURL>'
                   . '<DeclineURL>' . config('app.frontUrl') . '</DeclineURL>'
                   . '<AddParams>'
                   . '<OrderExpirationPeriod>' . self::ORDER_EXPIRATION_PERIOD . '</OrderExpirationPeriod>'
                   . '</AddParams>'
                   . '</Order>'
                   . '</Request>'
                   . '</TKKPG>';

        }

        if ( $type === 'getOrderStatus' ) {

            [$orderId, $sessionId] = explode('--', $order->pp_code);

            if ( $orderId === '' || $sessionId === '' ) {
                throw new \InvalidArgumentException('Invalid pp_code.');
            }

            return '<?xml version="1.0" encoding="UTF-8"?>'
                   . '<TKKPG>'
                   . '<Request>'
                   . '<Operation>GetOrderStatus</Operation>'
                   . '<Language>RU</Language>'
                   . '<Order>'
                   . '<Merchant>' . $this->merchant . '</Merchant>'
                   . '<OrderID>' . $orderId . '</OrderID>'
                   . '</Order>'
                   . '<SessionID>' . $sessionId . '</SessionID>'
                   . '</Request>'
                   . '</TKKPG>';
        }
    }

    /**
     * @param Response $responseObj
     *
     * @return array
     * @throws JsonException
     * @throws RegisterOrderException
     */
    protected function prepareRegisterOrderResponseData(Response $responseObj): array
    {
        $response = simplexml_load_string($responseObj->getResponse());

        if ( $response instanceof \SimpleXMLElement === false ) {
            throw new RegisterOrderException('Cant load xml response from MinBank.');
        }

        $response = json_decode(json_encode((array) $response, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        if ( (array_key_exists('Response', $response) && is_array($response['Response'])
              && array_key_exists('Status', $response['Response']) && is_string($response['Response']['Status'])
              && array_key_exists('Order', $response['Response']) && is_array($response['Response']['Order'])
              && array_key_exists('OrderID', $response['Response']['Order']) && is_string($response['Response']['Order']['OrderID'])
              && array_key_exists('SessionID', $response['Response']['Order']) && is_string($response['Response']['Order']['SessionID'])
              && array_key_exists('URL', $response['Response']['Order']) && is_string($response['Response']['Order']['URL'])
             ) === false ) {
            $response['error'] = [
                'code'    => null,
                'message' => 'Malformed MinBank register order response structure. Response: ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ];

            return $response;
        }

        if ( $response['Response']['Status'] !== self::REGISTER_ORDER_SUCCESS ) {
            $response['error'] = ['code' => $response['Response']['Status'], 'message' => $this->getRegisterOrderStatusList()[$response['Response']['Status']]];

            return $response;
        }

        $response['redirectUrl'] = $response['Response']['Order']['URL'] . '?' . http_build_query([
                'SESSIONID' => $response['Response']['Order']['SessionID'],
                'ORDERID'   => $response['Response']['Order']['OrderID'],
            ]);

        $response['success'] = true;

        return $response;
    }

    /**
     * @param Response $responseObj
     *
     * @return array
     * @throws GetOrderStatusException
     * @throws JsonException
     */
    protected function prepareGetOrderStatusResponseData(Response $responseObj): array
    {
        $response = simplexml_load_string($responseObj->getResponse());

        if ( $response instanceof \SimpleXMLElement === false ) {
            throw new GetOrderStatusException('Cant load xml response from MinBank.');
        }

        $response = json_decode(json_encode((array) $response, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        if ( (array_key_exists('Response', $response) && is_array($response['Response'])
              && array_key_exists('Status', $response['Response']) && is_string($response['Response']['Status'])
              && array_key_exists('Order', $response['Response']) && is_array($response['Response']['Order'])
              && array_key_exists('OrderID', $response['Response']['Order']) && is_string($response['Response']['Order']['OrderID'])
              && array_key_exists('OrderStatus', $response['Response']['Order']) && is_string($response['Response']['Order']['OrderStatus'])
             ) === false ) {
            $response['error'] = [
                'code'    => null,
                'message' => 'Malformed MinBank get order status response structure. Response: ' . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ];

            return $response;
        }

        if ( in_array($response['Response']['Order']['OrderStatus'], self::GET_ORDER_STATUS_FAILED_LIST, true) === true ) {

            $response['error'] = ['message' => $this->getOrderStatusList()[$response['Response']['Order']['OrderStatus']]];

        } elseif ( in_array($response['Response']['Order']['OrderStatus'], self::GET_ORDER_STATUS_SUCCESS_LIST, true) === true ) {

            $response['success'] = true;
        }

        return array_merge($response, ['responder_ip' => $responseObj->getStats()->getResponderIp()]);
    }

    /**
     * @return array
     */
    protected function getRegisterOrderStatusList(): array
    {
        return $this->registerOrderStatusList;
    }

    /**
     * @return array
     */
    protected function getOrderStatusList(): array
    {
        return $this->getOrderStatusList;
    }
}
