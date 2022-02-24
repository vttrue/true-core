<?php

namespace TrueCore\App\Libraries;

use TrueCore\App\Libraries\Payment\Exceptions\NetworkException;

class Shortener
{
    protected string $accessToken;
    protected string $groupGuid;
    protected string $apiUrl = 'https://api-ssl.bitly.com/v4';

    /**
     * Shortener constructor.
     *
     * @param string $accessToken
     * @param string $groupGuid
     */
    public function __construct(string $accessToken, string $groupGuid)
    {
        $this->accessToken = $accessToken;
        $this->groupGuid = $groupGuid;
    }

    /**
     * @return array|string[]
     */
    protected function getHeaders(): array
    {
        return [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken,
        ];
    }

    /**
     * @param string $url
     *
     * @return string
     * @throws NetworkException
     */
    public function shorten(string $url): ?string
    {
        $data = ['group_guid' => $this->groupGuid, 'domain' => 'bit.ly', 'long_url' => $url];

        $response = $this->makeRequest($this->apiUrl . '/shorten', $data, 'POST', $this->getHeaders());

        if ( array_key_exists('link', $response) && is_string($response['link']) && $response['link'] !== '' ) {
            return $response['link'];
        }

        return null;
    }

    /**
     * Makes a request to the payment processor server
     *
     * @param string $url
     * @param array  $data
     * @param string $method
     * @param array  $headers
     *
     * @return array
     * @throws NetworkException
     */
    protected function makeRequest($url, array $data = [], $method = 'GET', array $headers = []): array
    {
        $isPost = false;

        switch (strtoupper($method)) {
            case 'POST':
                $isPost = true;
                break;
            case 'GET':
            default:
                $url = $url . '?' . http_build_query($data);
                break;
        }

        $ch = curl_init();

        if ( !$ch || curl_errno($ch) !== 0 ) {
            throw new NetworkException('An error occurred. Request could not be sent. Curl error: ' . curl_error($ch)); // curl failure exception
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        if ( $isPost ) {

            curl_setopt($ch, CURLOPT_POST, 1);
            $requestData = ((in_array('Content-Type: application/json', $headers)) ? json_encode($data) : http_build_query($data));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ( count($headers) > 0 ) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLE_OPERATION_TIMEOUTED, 120);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ( $response ) {

            $json = json_decode($response, true);
            $json = ((is_array($json)) ? $json : []);

            return ((count($json) > 0) ? array_merge($json, ['rawResponse' => $response, 'responder_ip' => $info['primary_ip']]) : ['rawResponse' => $response, 'responder_ip' => $info['primary_ip']]);

        } else {

            if ( curl_errno($ch) !== 0 ) {
                throw new NetworkException('An error occurred. Request could not be sent. Curl error: ' . curl_error($ch)); // curl failure exception
            }

            return ['rawResponse' => '', 'responder_ip' => $info['primary_ip']];
        }
    }
}
