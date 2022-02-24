<?php
/**
 * Created by PhpStorm.
 * User: ANDREY
 * Date: 25.04.2019
 * Time: 12:19
 */

namespace TrueCore\App\Libraries\Payment\Handlers;

use Illuminate\Support\Str;

class SberbankHandler extends AlfaBankHandler
{
    protected $testUrl = 'https://3dsec.sberbank.ru/payment/rest/';
    protected $baseUrl = 'https://securepayments.sberbank.ru/payment/rest/';

    protected $currencyList = [
        840 => 'USD',
        980 => 'UAH',
        643 => 'RUB',
        946 => 'RON',
        398 => 'KZT',
        417 => 'KGS',
        392 => 'JPY',
        826 => 'GBR',
        978 => 'EUR',
        156 => 'CNY',
        974 => 'BYR',
    ];

    /**
     * @return array
     * @throws \JsonException
     */
    public function registerOrder(): array
    {
        $orderData = $this->getOrder()->getOrderData();

        $cartItemList = $this->getPaymentCart($orderData);

        $orderData = array_merge($orderData, [
            'additional_params' => [
                'orderBundle' => json_encode([
                    'cartItems' => [
                        'items' => array_values($cartItemList),
                    ],
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ],
        ]);

        if ( array_key_exists('orderData', $orderData) && is_array($orderData['orderData'])
             && array_key_exists('id', $orderData['orderData']) && is_numeric($orderData['orderData']['id']) && (int) $orderData['orderData']['id'] > 0 ) {

            $orderData['order_id'] = $orderData['orderData']['id'];
        }

        $this->getOrder()->setOrderData($orderData);

        return parent::registerOrder();
    }

    /**
     * @param array $orderData
     *
     * @return array
     */
    private function getPaymentCart(array $orderData): array
    {
        $totalPriceWithoutDiscount = array_sum(array_column(array_filter($orderData['orderData']['totals'], static function($v) {
            return $v['value'] >= 0 && $v['status'] === true;
        }), 'value'));

        $totalPrice = array_sum(array_column(array_filter($orderData['orderData']['totals'], static function($v) {
            return $v['status'] === true;
        }), 'value'));

        $discount = abs(array_sum(array_column(array_filter($orderData['orderData']['totals'], static function($v) {
                return $v['value'] <= 0 && $v['status'] === true;
            }), 'value'))) * 100;

        $productList = array_values(array_filter($orderData['orderData']['productList'], static fn($v) => ((float) $v['actualPrice'] > 0.0)));

        $productOriginalPriceList = [];
        $codes = [];

        $productTotalPricePercentage = [];

        // Для каждого продукта считаем, какую часть общая цена одного продукта составляет от общей цены без учёта скидок (для равномерного распределения скидки по каждому продукту)
        foreach ($productList as $k => $item) {
            $productTotalPricePercentage[$k] = (($item['totalPrice'] * 100) / ($totalPriceWithoutDiscount * 100));
        }

        $cartItemList = array_map(static function($v, $k) use (&$codes, $discount, $productTotalPricePercentage, &$productOriginalPriceList) {

            $productActualPrice = $v['actualPrice'] * 100;
            $productOriginalPriceList[$k] = $productActualPrice;

            // Считаем скидку для конкретного продукта, как общая скидка корзины, помноженная на часть общей цены продукта от общей цены всех товаров
            $discountForItem = ($discount * $productTotalPricePercentage[$k]) / $v['quantity'];

            if ( $v['id'] === null ) {
                $code = $v['additionalInfo']['bundle']['id'] . '-bundle';
            } elseif ( in_array($v['id'], $codes, true) === false ) {
                $code = $v['id'];
            } else {
                $code = $v['id'] . strtolower(Str::random(3));
            }

            $codes[] = $code;

            return [
                'positionId'   => ($k + 1),
                'name'         => $v['name'],
                'quantity'     => [
                    'value'   => (int) $v['quantity'],
                    'measure' => 'шт',
                ],
                'itemPrice'    => round(($productActualPrice - $discountForItem), 0, PHP_ROUND_HALF_UP),
                'itemAmount'   => round((($productActualPrice * $v['quantity']) - ($discountForItem * $v['quantity'])), 0, PHP_ROUND_HALF_UP),
                'itemCode'     => $code,
                'itemDetails'  => new \stdClass(),
                'itemCurrency' => 643,
            ];

        }, $productList, array_keys($productList));

        $deliveryPriceTotalList = array_filter($orderData['orderData']['totals'], static fn($v) => ($v['code'] === 'delivery_price'));

        if ( count($deliveryPriceTotalList) > 0 ) {

            $deliveryPriceTotal = reset($deliveryPriceTotalList);

            $cartItemList[] = [
                'positionId'   => (count($cartItemList) + 1),
                'name'         => 'Доставка',
                'quantity'     => [
                    'value'   => 1,
                    'measure' => 'шт',
                ],
                'itemPrice'    => round(($deliveryPriceTotal['value'] * 100), 0, PHP_ROUND_HALF_UP),
                'itemAmount'   => round(($deliveryPriceTotal['value'] * 100), 0, PHP_ROUND_HALF_UP),
                'itemCode'     => 'Shipping',
                'itemDetails'  => new \stdClass(),
                'itemCurrency' => 643,
            ];
        }

        $cartItemTotalPrice = (float) array_sum(array_column($cartItemList, 'itemAmount'));
        $totalPriceMinValue = (float) ($totalPrice * 100);

        if ( $totalPriceMinValue !== $cartItemTotalPrice ) {
            $mistakenDiff = ($totalPriceMinValue - $cartItemTotalPrice);
            $cartItemCount = count($cartItemList);
            $hasOnlyOneItemIndex = null;
            for ($i = 0; $i < ($cartItemCount - (($orderData['orderData']['delivery']['method']['price'] > 0) ? 1 : 0)); $i++) {
                if ( $hasOnlyOneItemIndex === null && $cartItemList[$i]['quantity']['value'] === 1 ) {
                    $hasOnlyOneItemIndex = $i;
                }
                if ( $cartItemList[$i]['quantity']['value'] > 1 ) {
                    // EL S.M.E.K.A.L.O.C.H.K.A.
                    $cartItemList[$i]['quantity']['value'] -= 1;
                    $cartItemList[$i]['itemAmount'] -= $cartItemList[$i]['itemPrice'];
                    $newItemIndex = ($cartItemCount - 1);
                    if ( $orderData['orderData']['delivery']['method']['price'] > 0 ) {
                        $cartItemList[$newItemIndex + 1] = $cartItemList[$newItemIndex];
                        $cartItemList[$newItemIndex + 1]['positionId'] += 1;
                    }
                    $cartItemList[$newItemIndex] = $cartItemList[$i];
                    $cartItemList[$newItemIndex]['quantity']['value'] = 1;
                    $newItemPrice = ($cartItemList[$newItemIndex]['itemPrice'] + $mistakenDiff);
                    $cartItemList[$newItemIndex]['itemAmount'] = $newItemPrice;
                    $cartItemList[$newItemIndex]['itemPrice'] = ((array_sum(array_column($cartItemList, 'itemAmount')) > $totalPriceMinValue) ? floor($newItemPrice) : ceil($newItemPrice));
                    $cartItemList[$newItemIndex]['itemAmount'] = ($cartItemList[$newItemIndex]['itemPrice'] * $cartItemList[$newItemIndex]['quantity']['value']);
                    $cartItemList[$newItemIndex]['itemCode'] = '_' . $cartItemList[$newItemIndex]['itemCode'];
                    $cartItemList[$newItemIndex]['positionId'] = ($newItemIndex + 1);
                    $hasOnlyOneItemIndex = null;
                    break;
                }
            }
            if ( $hasOnlyOneItemIndex !== null ) {
                $cartItemList[$hasOnlyOneItemIndex]['itemPrice'] += $mistakenDiff;
                $cartItemList[$hasOnlyOneItemIndex]['itemAmount'] = ($cartItemList[$hasOnlyOneItemIndex]['itemPrice'] * $cartItemList[$hasOnlyOneItemIndex]['quantity']['value']);
            }
        }

        return $cartItemList;
    }
}
