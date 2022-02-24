<?php
/**
 * Created by PhpStorm.
 * User: deprecator
 * Date: 19.10.15
 * Time: 11:18
 */

namespace TrueCore\App\Libraries\Payment;

use TrueCore\App\Libraries\Payment\Exceptions\{
    InvalidCurrencyException,
    OrderNotFoundException,
    PaymentHandlerException
};
use \TrueCore\App\Libraries\Payment\Handlers\PaymentHandlerInterface;
use \TrueCore\App\Libraries\Payment\Models\PaymentOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use PhpParser\Node\Expr\Closure;

/**
 * Class Payment
 *
 * @property string $sessionName
 * @property array $paymentSetting
 *
 * @property-read PaymentHandlerInterface $_handler
 * @property-read PaymentOrder $_order
 *
 * @package TrueCore\App\Libraries\Payment
 *
 * @TODO: enhance __construct to make the component more flexible, add success/fail/process url default values | Deprecator @ 2018-11-30
 */
class PaymentManualCode extends Payment
{
    private $_handler;
    private $_order;

    private $_transaction = false;

    private $_remoteResponse = [];

    /**
     * Creates a new payment order
     * @TODO: implement currency assigning
     *
     * @param integer $paymentProcessor
     * @param integer $currency
     * @param array $data
     * @return mixed
     */
    public function registerOrder($paymentProcessor, $currency, array $data)
    {
        try {
            $this->_order = self::makeOrder($paymentProcessor, $data);

            $handlerInstance = $this->getPaymentHandler($paymentProcessor);

            $currencyCode = array_search($currency, $handlerInstance->getCurrencyList());

            if ($currencyCode === false) {
                throw new InvalidCurrencyException('invalid currency code');
            }

            $this->_order->currency = $currencyCode;
            $orderData = $this->getOrderData();

            //echo json_encode($orderData);die;

            if (array_key_exists('processUrl', $orderData)) {
                $orderData['processUrl'] = str_replace('orderCode', $this->getOrder()->code, $orderData['processUrl']);
                $this->getOrder()->setOrderData($orderData);
            }

            $response = $handlerInstance->registerOrder();

            $this->saveOrderToSession();
            if ($this->isResponseSuccessful($response)) {
                $this->saveOrderToDatabase();
                $this->checkResult($response);
            }
            $this->_handler = null;
        } catch (\Exception $e) {

            var_dump($e->getMessage(), $e->getFile(), $e->getLine());
            die;
            //self::getLogger()->log('Something went wrong while during order registration: Message: '.$e->getMessage().' | File: '.$e->getFile().' | Line: '.$e->getLine(), Logger::LEVEL_ERROR);
            $response = [];
        }

        return $response;
    }

    /**
     * Makes an order entry for further processing
     *
     * @param integer $paymentProcessor
     * @param array $data
     * @return PaymentOrder
     */
    private static function makeOrder($paymentProcessor, array $data): PaymentOrder
    {
        $data['payment_processor'] = $paymentProcessor;

        //var_dump($data);die;

        $data['orderData'] = $data;
        $data['initiator_ip'] = $data['initiator_ip'] ?? '';

        $order = Models\PaymentOrder::makeOrder($data);

        if (
            array_key_exists('orderData', $data) &&
            is_array($data['orderData']) &&
            array_key_exists('orderData', $data['orderData']) &&
            is_array($data['orderData']['orderData']) &&
            array_key_exists('order_id', $data['orderData']['orderData']) &&
            (is_int($data['orderData']['orderData']['id']) || is_string($data['orderData']['orderData']['id']))
        ) {

            $order->code = $data['orderData']['orderData']['id'];
            //$order->save();
        }

        return $order;
    }

    /**
     * Gets the order entry to work with
     *
     * @return PaymentOrder|null
     */
    protected function getOrder(): ?PaymentOrder
    {
        return (($this->_order) ?: $this->getOrderFromSession());
    }
}
