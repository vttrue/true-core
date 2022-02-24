<?php

namespace TrueCore\App\Libraries\Payment\Handlers;

use \TrueCore\App\Libraries\Payment\Exceptions\NetworkException;
use TrueCore\App\Libraries\Traits\Restful;

/**
 * Class CloudPaymentsHandler
 *
 * @package TrueCore\App\Libraries\Payment\Handlers
 */
class CloudPaymentsHandler extends BasePaymentHandler
{
    use Restful;

    protected $baseUrl           = 'https://api.cloudpayments.ru/';
    protected $restMethods       = [
        'getOrderStatus' => 'payments/find',
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
    protected $expiredTime       = 86400;
    protected $statusCheckRemote = false;   // Payment status check is performed locally by requesting order status from the payment processor

    public $publicId;
    public $apiKey;
    public $preAuth = false;

    private $_orderStatusResponse = [];

    /**
     * @return array
     */
    private function getReceiptInfo(): array
    {
        $orderData = $this->getOrder()->getOrderData()['orderData'];

        // Считаем всё в копейках, чтобы не было расхождений
        $totalPriceWithoutDiscount = array_sum(array_column(array_filter($orderData['totals'], static function($v) {
                return $v['value'] >= 0 && $v['status'] === true;
            }), 'value')) * 100;
        $totalPrice = array_sum(array_column(
                array_filter($orderData['totals'], static fn($v) => ($v['status'] === true)),
                'value')) * 100;
        $discount = abs(array_sum(array_column(
                array_filter($orderData['totals'], static fn($v) => ($v['value'] <= 0 && $v['status'] === true)),
                'value'))) * 100;

        // Передаём в чек только товары с ненулевой ценой, подарки не считаем
        $receiptProductList = array_values(array_filter($orderData['productList'], static fn($v) => ((float) $v['actualPrice'] > 0.0)));

        $productTotalPricePercentage = [];

        // Для каждого продукта считаем, какую часть общая цена одного продукта составляет от общей цены без учёта скидок (для равномерного распределения скидки по каждому продукту)
        foreach ($receiptProductList as $k => $item) {
            $productTotalPricePercentage[$k] = (($item['totalPrice'] * 100) / ($totalPriceWithoutDiscount));
        }

        $items = array_map(static function($product, $k) use ($discount, $productTotalPricePercentage): array {

            // Считаем скидку для конкретного продукта, как общая скидка корзины, помноженная на часть общей цены продукта от общей цены всех товаров
            $discountForItem = ($discount * $productTotalPricePercentage[$k]) / $product['quantity'];

            return [
                'label'           => $product['name'],
                'price'           => round(($product['actualPrice'] * 100 - $discountForItem), 0, PHP_ROUND_HALF_UP),
                'quantity'        => $product['quantity'],
                'amount'          => round((($product['actualPrice'] * 100 * $product['quantity']) - ($discountForItem * $product['quantity'])), 0, PHP_ROUND_HALF_UP),
                'vat'             => 0, //ставка НДС
                'method'          => 0, // тег-1214 признак способа расчета - признак способа расчета
                'object'          => 0, // тег-1212 признак предмета расчета - признак предмета товара, работы, услуги, платежа, выплаты, иного предмета расчета
                'measurementUnit' => 'шт',
            ];
        }, $receiptProductList, array_keys($receiptProductList));

        $totals = array_filter($orderData['totals'], static fn($v) => ($v['code'] !== 'product_total_price' && $v['value'] > 0 && $v['status'] === true));

        if ( count($totals) > 0 ) {

            $items = [
                ...$items, ...array_map(static fn($v): array => [
                    'label'           => $v['title'],
                    'price'           => $v['value'] * 100,
                    'quantity'        => 1,
                    'amount'          => $v['value'] * 100,
                    'vat'             => 0,
                    'method'          => 0,
                    'object'          => 0,
                    'measurementUnit' => 'шт',
                ], $totals),
            ];
        }

        $cartItemTotalPrice = (float) array_sum(array_column($items, 'amount'));
        $totalPriceMinValue = (float) $totalPrice;

        if ( $totalPriceMinValue !== $cartItemTotalPrice ) {

            $mistakenDiff = ($totalPriceMinValue - $cartItemTotalPrice);
            $cartItemCount = count($items);
            $hasOnlyOneItemIndex = null;

            for ($i = 0; $i < $cartItemCount; $i++) {

                if ( $hasOnlyOneItemIndex === null && $items[$i]['quantity'] === 1 ) {
                    $hasOnlyOneItemIndex = $i;
                }

                if ( $items[$i]['quantity'] > 1 ) {
                    // EL S.M.E.K.A.L.O.C.H.K.A.
                    $items[$i]['quantity'] -= 1;
                    $items[$i]['amount'] -= $items[$i]['price'];
                    $newItemIndex = $cartItemCount;
//                    if ($orderData['orderData']['delivery']['method']['price'] > 0) {
//                        $cartItemList[$newItemIndex + 1] = $cartItemList[$newItemIndex];
//                        $cartItemList[$newItemIndex + 1]['positionId'] += 1;
//                    }
                    $items[$newItemIndex] = $items[$i];
                    $items[$newItemIndex]['quantity'] = 1;
                    $newItemPrice = ($items[$newItemIndex]['price'] + $mistakenDiff);
                    $items[$newItemIndex]['amount'] = $newItemPrice;
                    $items[$newItemIndex]['price'] = ((array_sum(array_column($items, 'amount')) > $totalPriceMinValue) ? floor($newItemPrice) : ceil($newItemPrice));
                    $items[$newItemIndex]['amount'] = ($items[$newItemIndex]['price'] * $items[$newItemIndex]['quantity']);

                    $hasOnlyOneItemIndex = null;
                    break;
                }
            }

            if ( $hasOnlyOneItemIndex !== null ) {
                $items[$hasOnlyOneItemIndex]['price'] += $mistakenDiff;
                $items[$hasOnlyOneItemIndex]['amount'] = ($items[$hasOnlyOneItemIndex]['price'] * $items[$hasOnlyOneItemIndex]['quantity']);
            }
        }

        $items = array_map(static function($item): array {

            $item['price'] /= 100;
            $item['amount'] /= 100;

            return $item;

        }, $items);

        $receiptInfo = [
            'Items'     => $items,
            'phone'     => $orderData['phone'],
            'isBso'     => false, //чек является бланком строгой отчётности
            'AgentSign' => null, //признак агента, тег ОФД 1057
            'amounts'   => [
                'electronic'     => round($orderData['totalPrice'], 2, PHP_ROUND_HALF_UP), // Сумма оплаты электронными деньгами
                'advancePayment' => 0.00, // Сумма из предоплаты (зачетом аванса) (2 знака после запятой)
                'credit'         => 0.00, // Сумма постоплатой(в кредит) (2 знака после запятой)
                'provision'      => 0.00 // Сумма оплаты встречным предоставлением (сертификаты, др. мат.ценности) (2 знака после запятой)
            ],
        ];

        if ( $orderData['name'] !== null ) {
            $receiptInfo['customerInfo'] = $orderData['name'];
        }

        if ( $orderData['email'] !== null ) {
            $receiptInfo['email'] = $orderData['email'];
        }

        return $receiptInfo;
    }

    /**
     * @inheritdoc
     */
    public function registerOrder(): array
    {
        $order = $this->getOrder();

        $order->setMargin($this->margin);

        $orderData = $order->getOrderData()['orderData'];

        $orderResponse = [
            'success' => true,
            'data'    => [
                'publicId'  => $this->publicId,
                'invoiceId' => $orderData['id'],
                'receipt'   => $this->getReceiptInfo(),
            ],
        ];

        $order->setExternalCode($orderData['id']);

        return $orderResponse;
    }

    /**
     * @return array|string[]
     */
    protected function getHeaders(): array
    {
        return ['Authorization' => 'Basic ' . base64_encode($this->publicId . ':' . $this->apiKey)];
    }

    /**
     * @return bool
     */
    protected function isPaymentExpired(): bool
    {
        return ((time() - $this->expiredTime) >= $this->getOrder()->created_at->getTimestamp());
    }

    /**
     * @param array $response
     *
     * @return array
     */
    protected function prepareOrderStatusResponse(array $response): array
    {
        if ( array_key_exists('Success', $response) ) {

            if ( $response['Success'] === false ) {

                if ( array_key_exists('Model', $response) && is_array($response['Model']) && array_key_exists('Reason', $response['Model'])
                     && $response['Model']['Reason'] !== 'ThreeDomainSecure' && array_key_exists('Message', $response) && $response['Message'] !== 'Not found' ) {
                    $response['error'] = [
                        'code'    => $response['Model']['ReasonCode'],
                        'message' => $response['Model']['Reason'],
                    ];
                }

            } elseif ( $response['Success'] === true ) {

                $response['success'] = true;
            }
        }

        return $response;
    }

    /**
     * @inheritDoc
     *
     * @throws \JsonException
     * @throws \TrueCore\App\Libraries\Exceptions\RequestException
     */
    public function getOrderStatus(): array
    {
        if ( $this->isPaymentExpired() ) {
            return [
                'error' => [
                    'code'    => 404,
                    'message' => 'Payment processor order has been expired',
                ],
            ];
        }

        $order = $this->getOrder();

        $data = ['invoiceId' => $order['pp_code']];

        $responseObj = $this->makeRequest($this->baseUrl . $this->restMethods['getOrderStatus'], 'POST', $data, $this->getHeaders());
        $statusResponse = array_merge($responseObj->getResponse(), ['responder_ip' => $responseObj->getStats()->getResponderIp()]);

        $order->setResponse($statusResponse);

        return $this->prepareOrderStatusResponse($statusResponse);
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
