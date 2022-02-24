<?php

namespace TrueCore\App\Libraries\Traits;

use TrueCore\App\Libraries\Loggers\RequestLogger;
use TrueCore\App\Libraries\Traits\Structures\Response;
use GuzzleHttp\Exception\GuzzleException;
use TrueCore\App\Libraries\Exceptions\RequestException;

use Illuminate\Support\Facades\Log;

/**
 * Trait Restful
 *
 * @package App\Libraries\Exchange\Traits
 */
trait Restful
{
    protected array $multimediaContentTypeList = ['application/octet-stream; charset=utf-8'];
    protected array $allowedRequestTypes       = ['GET', 'POST', 'PUT', 'DELETE'];
    protected array $defaultHeaders            = [
        'content-type' => 'application/json; charset=utf-8',
        'accept'       => 'application/json',
    ];

    /**
     * @param string $url
     * @param string $type
     * @param array  $data
     * @param array  $headers
     * @param bool   $verify
     *
     * @return Response
     * @throws RequestException
     */
    protected function makeRequest(string $url, string $type, $data = [], array $headers = [], bool $verify = true): Response
    {
        $type = strtoupper($type);

        if ( in_array($type, $this->allowedRequestTypes, true) === false ) {
            throw new \InvalidArgumentException('invalid request type. allowed: ' . implode(', ', $this->allowedRequestTypes));
        }

	Log::channel('Lg')->info(
                print_r($data, true) . "\n" . print_r($headers, true)
        );

        $client = new \GuzzleHttp\Client(['verify' => $verify]);

        try {

            $requestDuration = 0;
            $requestResponderIp = '';

            $requestData = $this->prepareRequestData($url, $type, $data, $headers);

            $response = $client->request($type, $requestData['url'],
                array_merge(
                    array_filter($requestData, static fn($k) => ($k !== 'url'), ARRAY_FILTER_USE_KEY),
                    [
                        'on_stats' => function(\GuzzleHttp\TransferStats $stats) use (&$requestDuration, &$requestResponderIp) {
                            $requestDuration = $stats->getTransferTime();
                            $requestResponderIp = $stats->getHandlerStat('primary_ip');
                        },
                    ]
                )
            );

        } catch (GuzzleException $e) {

            if ( method_exists($e, 'getResponse') ) {

                $exceptionResponse = $e->getResponse();

		Log::channel('Lg')->info(
                    $e->getMessage()
        	);

                /** SBIS тема - очень часто СБИС падает с 501 рандомно, продолжаем запрос, пока не выполнится */
                if ( is_object($exceptionResponse) && $exceptionResponse->getReasonPhrase() === 'Not Implemented' && $exceptionResponse->getStatusCode() === 501 ) {
                    return $this->makeRequest($url, $type, $data, $headers);
                }
            }

            throw new RequestException('request error', $e->getCode(), $e->getPrevious(), $url, $data, $headers, ['message' => $e->getMessage()]);
        }

        $responseContentType = $response->getHeaderLine('content-type');

        $response = $response->getBody()->getContents();

        try {
            if ( in_array($responseContentType, $this->multimediaContentTypeList, true) === true ) {
                $result = $response;
            } else {
                $result = ((is_string($response)) ? json_decode($response, true, 512, JSON_THROW_ON_ERROR) : $response);
            }
        } catch (\JsonException $e) {
            $result = $response;
        }

        if ( $requestDuration > 1.0 && in_array(Loggable::class, class_uses_recursive($this), true) ) {

            $this->setLogData(RequestLogger::class, 'requestWarning', [
                'warning' => [
                    'requestUrl'      => $url,
                    'requestData'     => (($type === 'GET') ? $requestData['query'] : $requestData['json']),
                    'requestHeaders'  => $requestData['headers'],
                    'requestDuration' => $requestDuration,
                    'requestType'     => $type,
                ],
            ]);
        }

        $stats = ['requestDuration' => $requestDuration, 'responderIp' => $requestResponderIp];

        return new Response([
            'request'     => [
                'requestUrl'     => $url,
                'requestData'    => (($type === 'GET') ? $requestData['query'] : ($requestData['json'] ?? $requestData['body'])),
                'requestHeaders' => $requestData['headers'],
                'requestType'    => $type,
            ], 'response' => $result, 'stats' => $stats,
        ]);
    }

    /**
     * @param string $xmlData
     *
     * @return array|string[]
     */
    private function prepareXMLRequestData(string $xmlData): array
    {
        return [
            'body' => $xmlData,
        ];
    }

    /**
     * @param string       $url
     * @param string       $type
     * @param array|string $data
     * @param array        $headers
     *
     * @return array{url: string, query: array, json: array, headers: array}
     */
    private function prepareRequestData(string $url, string $type, $data = [], array $headers = []): array
    {
        if ( array_key_exists('Content-Type', $headers) && stripos($headers['Content-Type'], 'text/xml') !== false ) {
            return $this->prepareXMLRequestData($data) + ['url' => $url, 'headers' => $headers];
        }

        if ( $type === 'GET' || (array_key_exists('Content-Type', $headers) && ($headers['Content-Type'] === 'application/x-www-form-urlencoded' || $headers['Content-Type'] === 'text/plain')) ) {

            $queryData = $data;
            $bodyData = [];

        } else {

            $bodyData = $data;

            $urlParts = parse_url($url);

            $queryData = [];

            if ( is_array($urlParts) && array_key_exists('query', $urlParts) && is_string($urlParts['query']) ) {

                $query = $urlParts['query'];

                if ( $query !== '' ) {
                    parse_str($query, $queryData);
                    $url = str_replace('?' . $query, '', $url);
                }
            }
        }

        return [
            'url'     => $url,
            'query'   => $queryData,
            'json'    => $bodyData,
            'headers' => $headers,
        ];
    }
}
