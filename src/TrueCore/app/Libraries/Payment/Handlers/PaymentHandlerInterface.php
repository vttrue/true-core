<?php
/**
 * Created by PhpStorm.
 * User: deprecator
 * Date: 21.10.15
 * Time: 8:59
 */

namespace TrueCore\App\Libraries\Payment\Handlers;

use \TrueCore\App\Libraries\Payment\Models\PaymentOrder;

interface PaymentHandlerInterface
{
    /**
     * Method to get the PaymentOrder instance
     *
     * @return PaymentOrder
     */
    public function getOrder() : PaymentOrder;

    /**
     * Method to check whether we work with the payment processor in test mode
     *
     * @return bool
     */
    public function isTestMode() : bool;

    /**
     * Method to check if the payment processor itself makes a request to our server
     * In order to check the validity of payment in process
     *
     * @return bool
     */
    public function isRemoteStatusCheck() : bool;

    /**
     * Method to register a payment order on the payment processor side
     *
     * @return array
     */
    public function registerOrder() : array;

    /**
     * Method to check payment status
     *
     * @return array
     */
    public function getOrderStatus() : array;


    /**
     * Sets remote data that was sent by payment processor if isRemoteStatusCheck is true
     *
     * @param array $remoteData
     */
    public function setRemoteData(array $remoteData) : void;

    public function getPaymentForm();

    public function processPayment(array $responseData);
}