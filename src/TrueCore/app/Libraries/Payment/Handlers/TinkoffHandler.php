<?php

namespace TrueCore\App\Libraries\Payment\Handlers;

use TrueCore\App\Libraries\Payment\Handlers\Consts\TinkoffOrderStatus;
use TrueCore\App\Libraries\Traits\Restful;
use TrueCore\App\Libraries\Exceptions\RequestException;

/**
 * Class TinkoffHandler
 *
 * @package TrueCore\App\Libraries\Payment\Handlers
 */
class TinkoffHandler extends BasePaymentHandler
{
    use Restful;

    public string $terminal;
    public string $password;

    protected $baseUrl = 'https://securepay.tinkoff.ru/v2/';

    protected $restMethods = [
        'registerOrder'  => 'Init',
        'getOrderStatus' => 'GetState',
    ];

    protected $currencyList      = [
        643 => 'RUB',
    ];
    protected $statusCheckRemote = true;

    /**
     * @param array $orderData
     *
     * @return array
     */
    private function getReceipt(array $orderData): array
    {
        $totalPriceWithoutDiscount = array_sum(array_column(array_filter($orderData['totals'], static function($v) {
            return $v['value'] >= 0 && $v['status'] === true;
        }), 'value'));

        $totalPrice = array_sum(array_column(array_filter($orderData['totals'], static function($v) {
            return $v['status'] === true;
        }), 'value'));

        $discount = abs(array_sum(array_column(array_filter($orderData['totals'], static function($v) {
                return $v['value'] <= 0 && $v['status'] === true;
            }), 'value'))) * 100;

        $productList = array_values(array_filter($orderData['productList'], static fn($v) => ((float) $v['actualPrice'] > 0.0)));

        $productOriginalPriceList = [];

        $productTotalPricePercentage = [];

        // Для каждого продукта считаем, какую часть общая цена одного продукта составляет от общей цены без учёта скидок (для равномерного распределения скидки по каждому продукту)
        foreach ($productList as $k => $item) {
            $productTotalPricePercentage[$k] = (($item['totalPrice'] * 100) / ($totalPriceWithoutDiscount * 100));
        }

        $cartItemList = array_map(static function($v, $k) use ($discount, $productTotalPricePercentage, &$productOriginalPriceList) {

            $productActualPrice = $v['actualPrice'] * 100;
            $productOriginalPriceList[$k] = $productActualPrice;

            // Считаем скидку для конкретного продукта, как общая скидка корзины, помноженная на часть общей цены продукта от общей цены всех товаров
            $discountForItem = ($discount * $productTotalPricePercentage[$k]) / $v['quantity'];

            return [
                'Name'     => $v['name'],
                'Quantity' => (int) $v['quantity'],
                'Tax'      => 'none',
                'Price'    => round(($productActualPrice - $discountForItem), 0, PHP_ROUND_HALF_UP),
                'Amount'   => round((($productActualPrice * $v['quantity']) - ($discountForItem * $v['quantity'])), 0, PHP_ROUND_HALF_UP),
            ];

        }, $productList, array_keys($productList));

        $deliveryPriceTotalList = array_filter($orderData['totals'], static fn($v) => ($v['code'] === 'delivery_price'));

        $deliveryPrice = 0.0;

        if ( count($deliveryPriceTotalList) > 0 ) {

            $deliveryPriceTotal = reset($deliveryPriceTotalList);

            $deliveryPrice = $deliveryPriceTotal['value'] * 100;

            $cartItemList[] = [
                'Name'     => 'Доставка',
                'Quantity' => 1,
                'Tax'      => 'none',
                'Price'    => round($deliveryPrice, 0, PHP_ROUND_HALF_UP),
                'Amount'   => round($deliveryPrice, 0, PHP_ROUND_HALF_UP),
            ];
        }

        $cartItemTotalPrice = (float) array_sum(array_column($cartItemList, 'Amount'));
        $totalPriceMinValue = (float) ($totalPrice * 100);

        if ( $totalPriceMinValue !== $cartItemTotalPrice ) {
            $mistakenDiff = ($totalPriceMinValue - $cartItemTotalPrice);
            $cartItemCount = count($cartItemList);
            $hasOnlyOneItemIndex = null;
            for ($i = 0; $i < ($cartItemCount - (($deliveryPrice > 0) ? 1 : 0)); $i++) {
                if ( $hasOnlyOneItemIndex === null && $cartItemList[$i]['Quantity'] === 1 ) {
                    $hasOnlyOneItemIndex = $i;
                }
                if ( $cartItemList[$i]['Quantity'] > 1 ) {
                    // EL S.M.E.K.A.L.O.C.H.K.A.
                    $cartItemList[$i]['Quantity'] -= 1;
                    $cartItemList[$i]['Amount'] -= $cartItemList[$i]['Price'];
                    $newItemIndex = ($cartItemCount - 1);
                    if ( $deliveryPrice > 0 ) {
                        $cartItemList[$newItemIndex + 1] = $cartItemList[$newItemIndex];
                    }
                    $cartItemList[$newItemIndex] = $cartItemList[$i];
                    $cartItemList[$newItemIndex]['Quantity'] = 1;
                    $newItemPrice = ($cartItemList[$newItemIndex]['Price'] + $mistakenDiff);
                    $cartItemList[$newItemIndex]['Amount'] = $newItemPrice;
                    $cartItemList[$newItemIndex]['Price'] = ((array_sum(array_column($cartItemList, 'Amount')) > $totalPriceMinValue) ? floor($newItemPrice) : ceil($newItemPrice));
                    $cartItemList[$newItemIndex]['Amount'] = ($cartItemList[$newItemIndex]['Price'] * $cartItemList[$newItemIndex]['Quantity']);
                    $hasOnlyOneItemIndex = null;
                    break;
                }
            }
            if ( $hasOnlyOneItemIndex !== null ) {
                $cartItemList[$hasOnlyOneItemIndex]['Price'] += $mistakenDiff;
                $cartItemList[$hasOnlyOneItemIndex]['Amount'] = ($cartItemList[$hasOnlyOneItemIndex]['Price'] * $cartItemList[$hasOnlyOneItemIndex]['Quantity']);
            }
        }

        return [
            'Email'    => $orderData['email'],
            'Phone'    => $orderData['phone'],
            'Taxation' => 'osn',
            'Items'    => $cartItemList,
        ];
    }

    /**
     * @return array
     *
     * @throws RequestException
     * @throws \JsonException
     */
    public function registerOrder(): array
    {
        $order = $this->getOrder();
        $orderData = $order->getOrderData();

        /** @TODO: не дёргать конфиг. Incarnator | 2020-05-05 */
        $data = [
            'TerminalKey' => $this->terminal,
            'Amount'      => (float) $orderData['amount'] * 100,
            'OrderId'     => $orderData['orderData']['id'],
            'Description' => 'Заказ №' . $orderData['orderData']['id'] . ' на сайте ' . config('app.frontUrl'),
            'orderData'   => $orderData['orderData'],
            'SuccessURL'  => $orderData['returnUrl'] ?? '',
            'FailURL'     => $orderData['failUrl'] ?? '',
            'Receipt'     => $this->getReceipt($orderData['orderData']),
        ];

        $data['Token'] = $this->generateToken($data);

        $this->getOrder()->setOrderData($data);

        $headers = $this->getRequestHeaders();

        $orderResponse = $this->makeRequest($this->baseUrl . $this->restMethods['registerOrder'], 'POST', $data, $headers)->getResponse();

        if ( array_key_exists('Success', $orderResponse) === false ) {

            $orderResponse['error'] = ['code' => null, 'message' => 'Unknown error'];

            return $orderResponse;
        }

        if ( $orderResponse['Success'] === false ) {

            $orderResponse['error'] = ['code' => $orderResponse['ErrorCode'], 'message' => $orderResponse['Message']];

            return $orderResponse;
        }

        if ( array_key_exists('PaymentURL', $orderResponse) && is_string($orderResponse['PaymentURL']) && $orderResponse['PaymentURL'] !== '' ) {
            $orderResponse['redirectUrl'] = $orderResponse['PaymentURL'];
        } else {
            $orderResponse['error'] = true;
        }

        if ( array_key_exists('PaymentId', $orderResponse) && is_string($orderResponse['PaymentId']) && $orderResponse['PaymentId'] !== '' ) {
            $order->setExternalCode($orderResponse['PaymentId']);
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

        $data = [
            'TerminalKey' => $this->terminal,
            'PaymentId'   => $order->pp_code,
        ];

        $data['Token'] = $this->generateToken($data);

        $responseObj = $this->makeRequest($this->baseUrl . $this->restMethods['getOrderStatus'], 'POST', $data, $headers);
        $statusResponse = array_merge($responseObj->getResponse(), ['responder_ip' => $responseObj->getStats()->getResponderIp()]);

        $order->setResponse($statusResponse);

        if ( array_key_exists('Success', $statusResponse) && is_bool($statusResponse['Success']) && $statusResponse['Success'] === true
             && array_key_exists('Status', $statusResponse) && is_string($statusResponse['Status']) && $statusResponse['Status'] !== '' ) {

            if ( in_array($statusResponse['Status'], [TinkoffOrderStatus::STATUS_REJECTED, TinkoffOrderStatus::STATUS_EXPIRED], true) === true ) {
                $message = ((array_key_exists('Message', $statusResponse) && is_string($statusResponse['Message']) && $statusResponse['Message'] !== '')
                    ? $statusResponse['Message'] : 'Unknown error');

                $statusResponse['error'] = [
                    'message' => $message,
                ];
            } elseif ( $statusResponse['Status'] === TinkoffOrderStatus::STATUS_CONFIRMED ) {
                $statusResponse['success'] = true;
            }
        }

        return $statusResponse;
    }

    /**
     * @return array
     */
    protected function getRequestHeaders(): array
    {
        $headers = [];

        $headers['Content-Type'] = 'application/json';

        return $headers;
    }

    /**
     * @param array $orderData
     *
     * @return string
     */
    private function generateToken(array $orderData): string
    {
        $dataToEncrypt = array_filter($orderData,
            static fn($key) => (in_array($key, ['Receipt', 'DATA', 'orderData'], true) === false),
            ARRAY_FILTER_USE_KEY);

        $dataToEncrypt['Password'] = $this->password;

        ksort($dataToEncrypt);

        return hash('sha256', implode('', $dataToEncrypt));
    }
}
