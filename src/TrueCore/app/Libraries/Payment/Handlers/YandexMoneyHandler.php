<?php
/**
 * Created by PhpStorm.
 * User: deprecator
 * Date: 19.05.17
 * Time: 11:56
 */

namespace TrueCore\App\Libraries\Payment\Handlers;

class YandexMoneyHandler extends BasePaymentHandler
{
    public $shopID;
    public $shopPassword;
    public $securityType = 'MD5';

    protected $currencyList = [
        10643 => 'RUB'
    ];
    protected $statusCheckRemote = true;

    public function registerOrder() : array
    {
        $order = $this->getOrder();
        $order->setMargin($this->margin);
        $orderData = $order->getOrderData();
        $orderData['action'] = 'checkOrder';
        $responseArray = $this->processRequest($orderData);
        $order->setExternalCode($orderData['invoiceId']);
        return $responseArray;
    }

    public function getOrderStatus() : array
    {
        $order = $this->getOrder();
        $request = $order->getOrderData();
        $request['action'] = 'paymentAviso';
        $responseArray = $this->processRequest($request);
        $order->setResponse(array_merge($responseArray, ['responder_ip' => $request['responder_ip']]));
        return $responseArray;
    }

    /**
     * Building XML response.
     * @param  string $functionName  "checkOrder" or "paymentAviso" string
     * @param  string $invoiceId     transaction number
     * @param  string $result_code   result code
     * @param  string $message       error message. May be null.
     * @return string|null                prepared XML response
     */
    private function buildResponse($functionName, $invoiceId, $result_code, $message = null) : ?string
    {
        try {
            $performedDatetime = self::formatDate(new \DateTime());
            $response = '<?xml version="1.0" encoding="UTF-8"?><' . $functionName . 'Response performedDatetime="' . $performedDatetime .
                '" code="' . $result_code . '" ' . ($message != null ? 'message="' . $message . '"' : '') . ' invoiceId="' . $invoiceId . '" shopId="' . $this->shopID . '"/>';
            return $response;
        } catch (\Exception $e) {
            //
        }
        return null;
    }

    /**
     * Processes "checkOrder" and "paymentAviso" requests.
     * @param array $request payment parameters
     *
     * @return array
     */
    public function processRequest($request) : array
    {
        if ($this->securityType === 'MD5') {
            // If the MD5 checking fails, respond with "1" error code
            if (!$this->checkMD5($request)) {
                $response = $this->buildResponse($request['action'], $request['invoiceId'], 1);
                return [
                    'error' => [
                        'code' => 1,
                        'message' => 'Invalid signature'
                    ],
                    'xml' => $response
                ];
            }
        } else if ($this->securityType === 'PKCS7') {
            // Checking for a certificate sign. If the checking fails, respond with "200" error code.
            if (($request = $this->verifySign()) == null) {
                $response = $this->buildResponse($request['action'], null, 200);
                return [
                    'error' => [
                        'code' => 200,
                        'message' => 'Invalid certificate sign'
                    ],
                    'xml' => $response
                ];
            }
        }
        $response = null;
        if ($request['action'] === 'checkOrder') {
            $response = $this->checkOrder($request);
        } else {
            $response = $this->paymentAviso($request);
        }
        return $response;
    }

    /**
     * CheckOrder request processing. We suppose there are no item with price less
     * than 1 ruble in the shop.
     * @param  array $request payment parameters
     * @return array prepared response array with xml
     */
    private function checkOrder($request) : array
    {
        $response = null;
        if ($request['orderSumAmount'] < 1) {
            $response = $this->buildResponse($request['action'], $request['invoiceId'], 100, 'The amount should be more than 1 ruble.');
            return [
                'error' => [
                    'code' => 100,
                    'message' => 'The amount should be more than 1 ruble.'
                ],
                'xml' => $response
            ];
        } else {
            $response = $this->buildResponse($request['action'], $request['invoiceId'], 0);
            return [
                'success' => true,
                'xml' => $response
            ];
        }
    }

    /**
     * PaymentAviso request processing.
     * @param  array $request -- payment parameters
     * @return array  -- prepared response in XML format
     */
    private function paymentAviso($request) : array
    {
        return [
            'success' => true,
            'xml' => $this->buildResponse($request['action'], $request['invoiceId'], 0)
        ];
    }

    /**
     * Checking for sign when XML/PKCS#7 scheme is used.
     * @return array if request is successful, returns key-value array of request params, null otherwise.
     */
    private function verifySign()
    {
        $descriptorspec = [
            0 => [
                'pipe',
                'r'
            ],
            1 => [
                'pipe',
                'w'
            ],
            2 => [
                'pipe',
                'w'
            ]
        ];
        $certificate = 'yamoney.pem';
        $process = proc_open('openssl smime -verify -inform PEM -nointern -certfile ' . $certificate . ' -CAfile ' . $certificate,
            $descriptorspec, $pipes);
        if (is_resource($process)) {
            // Getting data from request body.
            /*$data = file_get_contents($this->settings->request_source); // "php://input"
            fwrite($pipes[0], $data);
            fclose($pipes[0]);
            $content = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $resCode = proc_close($process);
            if ($resCode != 0) {
                return null;
            } else {
                $xml = simplexml_load_string($content);
                $array = json_decode(json_encode($xml), true);
                return $array['@attributes'];
            }*/
        }
        return null;
    }

    /**
     * Checking the MD5 sign.
     * @param  array $request payment parameters
     * @return bool true if MD5 hash is correct
     */
    private function checkMD5($request) : bool
    {
//        echo json_encode($request);die;
        $str = $request['action'] . ';' .
            $request['orderSumAmount'] . ';' . $request['orderSumCurrencyPaycash'] . ';' .
            $request['orderSumBankPaycash'] . ';' . $request['shopId'] . ';' .
            $request['invoiceId'] . ';' . trim($request['customerNumber']) . ';' . $this->shopPassword;
        $md5 = strtoupper(md5($str));
        return ($md5 === strtoupper($request['md5']));
    }

    /**
     * @param \DateTime $date
     * @return string
     */
    private static function formatDate(\DateTime $date) : string
    {
        $performedDatetime = $date->format('Y-m-d') . 'T' . $date->format('H:i:s') . '.000' . $date->format('P');
        return $performedDatetime;
    }

    /**
     * @param \DateTime $date
     * @return string
     */
    private static function formatDateForMWS(\DateTime $date) : string
    {
        $performedDatetime = $date->format('Y-m-d') . 'T' . $date->format('H:i:s') . '.000Z';
        return $performedDatetime;
    }
}
