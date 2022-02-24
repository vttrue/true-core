<?php
/**
 * Created by PhpStorm.
 * User: deprecator
 * Date: 21.10.15
 * Time: 11:02
 */

namespace TrueCore\App\Libraries\Payment\Handlers;

use \TrueCore\App\Libraries\Payment\Exceptions\NetworkException;
use \TrueCore\App\Libraries\Payment\Models\PaymentOrder;

/**
 * Class BasePaymentHandler
 *
 * @property PaymentOrder $_order
 * @property string $testUrl
 * @property string $baseUrl
 * @property array $restMethods
 * @property array $currencyList
 * @property bool $statusRemoteCheck
 * @property array $remoteData
 *
 * @property bool $testMode
 * @property float $margin
 *
 * @package TrueCore\App\Libraries\Payment\Handlers
 */
abstract class BasePaymentHandler implements PaymentHandlerInterface
{
    protected $_order;
    protected $testUrl           = '';
    protected $baseUrl           = '';
    protected $restMethods       = [];
    protected $currencyList      = [];
    protected $statusCheckRemote = true;    // Payment status check is performed by the payment processor by default
    protected $remoteData        = [];

    public $testMode = false;
    public $margin   = 0.00;

    /**
     * @param PaymentOrder $order
     * @param array $merchantCredentials
     */
    public function __construct(PaymentOrder $order, array $merchantCredentials)
    {
        $this->_order = $order;
        if (count($merchantCredentials) > 0) {
            foreach ($merchantCredentials AS $param => $value) {
                $this->{$param} = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getCurrencyList(): array
    {
        return $this->currencyList;
    }

    /**
     * @return PaymentOrder
     */
    public function getOrder(): PaymentOrder
    {
        return $this->_order;
    }

    /**
     * Whether the handler works in test mode
     *
     * @inheritdoc
     *
     * @return bool
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * Whether the payment status check is performed by the payment processor via making a http request
     * Otherwise a request to the payment processor must be made by the server
     *
     * @inheritdoc
     *
     * @return bool
     */
    public function isRemoteStatusCheck(): bool
    {
        return $this->statusCheckRemote;
    }

    /**
     * Sets remote data that was sent by payment processor if isRemoteStatusCheck is true
     *
     * @param array $remoteData
     */
    public function setRemoteData(array $remoteData): void
    {
        if ($this->isRemoteStatusCheck()) {
            $this->remoteData = $remoteData;
        }
    }

    /**
     * Gets remote data if available
     *
     * @return array
     */
    protected function getRemoteData(): array
    {
        if ($this->isRemoteStatusCheck()) {
            return $this->remoteData;
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    abstract public function registerOrder(): array;

    public function getPaymentForm()
    {
        //
    }

    /**
     * @inheritdoc
     */
    abstract public function getOrderStatus(): array;

    public function processPayment(array $responseData)
    {
        //
    }
}
