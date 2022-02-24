<?php

namespace TrueCore\App\Libraries\Notification;

use TrueCore\App\Libraries\Notification\{Exceptions\NetworkException, Handlers\BaseNotificationHandler};

/**
 * Class BaseHttpHandler
 *
 * @package TrueCore\App\Libraries\Notification
 */
abstract class BaseHttpHandler extends BaseNotificationHandler
{
    public string   $baseUrl;
    protected array $data;

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Makes a request to the notification processor server
     *
     * @param array $data
     * @param string $method
     * @param array $headers
     *
     * @return array
     * @throws NetworkException
     */
    protected function makeRequest(array $data = [], string $method = 'GET', array $headers = []): array
    {
        $isPost = false;

        $url = $this->getBaseUrl();

        $method = strtoupper($method);

        if ($method === 'POST') {
            $isPost = true;
        } else {
            $url = $url . '?' . http_build_query($data);
        }

        $ch = curl_init();

        if (!$ch || curl_errno($ch) !== 0) {
            throw new NetworkException('An error occurred. Request could not be sent. Curl error: ' . curl_error($ch)); // curl failure exception
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        if ($isPost) {

            curl_setopt($ch, CURLOPT_POST, 1);
            $requestData = ((in_array('Content-Type: application/json', $headers)) ? json_encode($data) : http_build_query($data));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLE_OPERATION_TIMEOUTED, 120);

        $response = curl_exec($ch);
        $info     = curl_getinfo($ch);

        if ($response) {

            $json = json_decode($response, true);
            $json = ((is_array($json)) ? $json : []);

            return ((count($json) > 0) ? array_merge($json, ['rawResponse' => $response, 'responder_ip' => $info['primary_ip']]) : ['rawResponse' => $response, 'responder_ip' => $info['primary_ip']]);

        } else {

            if (curl_errno($ch) !== 0) {
                throw new NetworkException('An error occurred. Request could not be sent. Curl error: ' . curl_error($ch)); // curl failure exception
            }

            return ['rawResponse' => '', 'responder_ip' => $info['primary_ip']];
        }
    }
}
