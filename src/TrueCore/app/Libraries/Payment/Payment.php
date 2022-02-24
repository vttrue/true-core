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
class Payment
{
    const PP_ALFABANK      = 1;
    const PP_YANDEXMONEY   = 2;
    const PP_NETPAY        = 3;
    const PP_SBERBANK      = 4;
    const PP_YANDEXKASSA   = 5;
    const PP_CLOUDPAYMENTS = 6;
    const PP_CREDITEUROPEBANK = 7;
    const PP_MINBANK       = 8;

    const PP_CUR_USD = 'USD';
    const PP_CUR_EUR = 'EUR';
    const PP_CUR_RUB = 'RUB';

    public $sessionName     = 'temporaryPaymentOrder';
    public $paymentSettings = [];

    private $_handler;
    private $_order;

    private $_transaction = false;

    private $_remoteResponse = [];

    /**
     * Payment constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $config = config('payment', []);

        foreach ($config as $param => $value) {
            $this->{$param} = $value;
        }

        DB::beginTransaction();

        $this->_transaction = true;
    }

    /**
     * Gets the currently active transaction
     *
     * @return bool
     */
    protected function getTransaction()
    {
        return $this->_transaction;
    }

    /**
     * Gets chosen payment handler
     *
     * @param integer|string $paymentProcessor
     *
     * @return PaymentHandlerInterface
     *
     * @throws PaymentHandlerException
     */
    protected function getPaymentHandler($paymentProcessor)
    {
        if ($this->_handler === null) {
            $this->setPaymentHandler($paymentProcessor);
        }

        return $this->_handler;
    }

    /**
     * Gets a list of available payment handlers
     *
     * @return array
     */
    private static function getPaymentHandlerList()
    {
        return [
            self::PP_ALFABANK      => 'AlfaBankHandler',
            self::PP_YANDEXMONEY   => 'YandexMoneyHandler',
            self::PP_NETPAY        => 'NetPayHandler',
            self::PP_SBERBANK      => 'SberbankHandler',
            self::PP_YANDEXKASSA   => 'YandexKassaHandler',
            self::PP_CLOUDPAYMENTS => 'CloudPaymentsHandler',
            self::PP_MINBANK       => 'MinBankHandler',
            self::PP_CREDITEUROPEBANK => 'CreditEuropeHandler'
        ];
    }

    /**
     * Gets the payment handler name by provided $id
     *
     * @param string|integer $id
     *
     * @return string | null
     */
    public function getPaymentHandlerName($id)
    {
        $handlers = self::getPaymentHandlerList();
        return (array_key_exists($id, $handlers)) ? $handlers[$id] : null;
    }

    /**
     * Sets and instantiates a payment handler for chosen payment processor
     *
     * @param string|int $paymentProcessor
     *
     * @throws PaymentHandlerException
     */
    protected function setPaymentHandler($paymentProcessor)
    {
        try {

            if (!array_key_exists($paymentProcessor, self::getPaymentHandlerList()) || (!is_string(self::getPaymentHandlerList()[$paymentProcessor]) || self::getPaymentHandlerList()[$paymentProcessor] === '')) {
                throw new PaymentHandlerException('No payment handler specified'); // no payment processor specified =(
            }

            $ppName    = self::getPaymentHandlerList()[$paymentProcessor];
            $reflector = new \ReflectionClass('\TrueCore\App\Libraries\Payment\Handlers\\' . $ppName);

            if (!$reflector->isInstantiable()) {
                throw new PaymentHandlerException('Payment Handler could not be instantiated'); // payment handler is not instantiable
            }
        } catch (\Exception $e) {
            throw new PaymentHandlerException('Payment Handler is incorrect -- ' . $e->getMessage()); // payment handler is incorrect
        }

        $instance       = $reflector->newInstanceArgs([
            'order'               => $this->getOrder(),
            'merchantCredentials' => ((array_key_exists($ppName, $this->paymentSettings)) ? $this->paymentSettings[$ppName] : []),
        ]);
        $this->_handler = $instance;
    }

    /**
     * Creates a new payment order
     *
     * @TODO: implement currency assigning
     *
     * @param integer $paymentProcessor
     * @param integer $currency
     * @param array $data
     *
     * @return mixed
     */
    public function registerOrder($paymentProcessor, $currency, array $data)
    {
        try {
            $this->_order    = self::makeOrder($paymentProcessor, $data);
            $handlerInstance = $this->getPaymentHandler($paymentProcessor);

            $currencyCode = array_search($currency, $handlerInstance->getCurrencyList());

            if ($currencyCode === false) {
                throw new InvalidCurrencyException('invalid currency code');
            }

            $this->_order->currency = $currencyCode;

            $orderData = $this->getOrderData();

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
            //dd($e->getMessage(), $e->getFile(), $e->getLine());
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
     *
     * @return PaymentOrder
     */
    private static function makeOrder($paymentProcessor, array $data): PaymentOrder
    {
        $data['payment_processor'] = $paymentProcessor;
        $data['orderData']         = $data;
        $data['initiator_ip']      = $data['initiator_ip'] ?? '';

        return Models\PaymentOrder::makeOrder($data);
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

    /**
     * Gets the order unique code
     *
     * @return string
     * @throws OrderNotFoundException
     */
    public function getOrderCode(): string
    {
        if (!$this->getOrder()) {
            throw new OrderNotFoundException('Could not get order');
        }
        return $this->getOrder()['code'];
    }

    /**
     * Sets response coming from the payment processor
     *
     * @param array $data
     */
    public function setExternalResponse(array $data)
    {
        $this->_remoteResponse = $data;
    }

    /**
     * Gets order processing status
     *
     * @param string $code
     * @param string $ppCode
     *
     * @return array
     * @throws OrderNotFoundException
     * @throws PaymentHandlerException
     */
    public function getOrderStatus(string $code, string $ppCode = ''): array
    {
        if ($ppCode !== '') {
            $this->_order = PaymentOrder::findOrderByPpCode($ppCode);
        } else {
            $this->_order = PaymentOrder::findOrderByCode($code);
        }

        if (!$this->getOrder()) {
            throw new OrderNotFoundException('Could not get order by code');
        }
        $handlerInstance = $this->getPaymentHandler($this->getOrder()['payment_processor']);

        if ($handlerInstance->isRemoteStatusCheck()) {
            $handlerInstance->setRemoteData($this->_remoteResponse);
        }

        return $handlerInstance->getOrderStatus();
    }

    /**
     * @return array
     * @throws OrderNotFoundException
     */
    public function getOrderData()
    {
        if (!$this->getOrder()) {
            throw new OrderNotFoundException('Could not get order');
        }

        return $this->getOrder()->getOrderData();
    }

    /**
     * Checks whether the response is successful and commits or rollbacks the currently active transaction
     *
     * @param array $response
     *
     * @return bool
     * @throws \Exception
     */
    public function checkResult(array $response): bool
    {
        if ($this->isResponseSuccessful($response)) {
            if ((int)$this->getOrder()['status'] === PaymentOrder::STATUS_PENDING) {
                $this->getOrder()->markAsProcessing();
            } elseif ((int)$this->getOrder()['status'] === PaymentOrder::STATUS_PROCESSING) {
                $this->getOrder()->markAsSuccessful();
            }

            $this->keepChanges();

            return true;
        } else {

            $this->discardChanges();

            if ((int)$this->getOrder()['status'] === PaymentOrder::STATUS_PROCESSING) {
                $this->getOrder()->markAsFailed();
            }

            return false;
        }
    }

    /**
     * Checks whether the response is successful
     *
     * @param array $response
     *
     * @return bool
     */
    public function isResponseSuccessful(array $response): bool
    {
        return (!array_key_exists('error', $response) && array_key_exists('success', $response) && $response['success']);
    }

    /**
     * Checks whether the ongoing order is stored in session
     *
     * @return bool
     */
    protected function isOrderInSession(): bool
    {
        return Session::has($this->sessionName);
    }

    /**
     * Get the currently active order from session
     *
     * @return Models\PaymentOrder|null
     */
    protected function getOrderFromSession()
    {
        if ($this->isOrderInSession()) {
            return unserialize(Session::get($this->sessionName));
        }
        return null;
    }

    /**
     * Saves the order inside the session
     */
    protected function saveOrderToSession()
    {
        Session::put($this->sessionName, serialize($this->getOrder()));
    }

    /**
     * Removes the order from session
     *
     * @return bool|mixed
     */
    protected function removeOrderFromSession()
    {
        if ($this->isOrderInSession()) {
            return unserialize(Session::remove($this->sessionName));
        }
        return false;
    }

    /**
     * Saves the order into the database
     *
     * @return bool
     */
    protected function saveOrderToDatabase(): bool
    {
        return ($this->removeOrderFromSession() && $this->getOrder()->save());
    }

    /**
     * Method to commit the currently active transaction
     *
     * @return void
     */
    public function keepChanges(): void
    {
        if ($this->getTransaction()) {
            DB::commit();
            $this->_transaction = false;
        }
    }

    /**
     * Method to roll back the currently active transaction
     *
     * @return void
     * @throws \Exception
     */
    public function discardChanges(): void
    {
        if ($this->getTransaction()) {
            DB::rollBack();
            $this->_transaction = false;
        }
    }
}
