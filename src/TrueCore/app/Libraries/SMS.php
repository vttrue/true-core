<?php

namespace TrueCore\App\Libraries;

use Hhxsv5\PhpMultiCurl\Curl;

class SMS
{
    const SMS_TYPE = 'sms';

    const VALIDITY_PERIOD_MIN = 60;
    const VALIDITY_PERIOD_MAX = 1440 * 60;

    protected $apiUrl;
    protected $login;
    protected $apiKey;

    public function __construct()
    {
        $this->login = config('sms.apiLogin');
        $this->apiKey = config('sms.apiKey');
        $this->apiUrl = config('sms.apiUrl');
    }

    /**
     * @param $to
     * @param $text
     * @param $from
     * @param string $route
     * @return mixed
     * @throws \Exception
     */
    public function send($to, $text, $from, $route = self::SMS_TYPE)
    {
        $methodUrl = 'message';
        $to = is_array($to) ? $to : [$to];

        $data = [
            'to'    => implode(',', $to),
            'text'  => $text,
            'from'  => $from,
            'route' => $route,
        ];

        return $this->sendPost($methodUrl, $data);
        //return [];
    }

    /**
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function sendMessage($data)
    {
        $methodUrl = 'message';

        $curl = new Curl;
        $curl->makeGet($this->apiUrl . '/message', $data, $this->getHeaders($data));
        $response = $curl->exec();
        if ($response->hasError()) {
            dd($response->getError());
        } else {
            dd($response->getBody());
        }

        /*$curlResource = curl_init();
        curl_setopt($curlResource, CURLOPT_URL, $this->apiUrl . "/" . $url);
        curl_setopt($curlResource, CURLOPT_POST, 1);
        curl_setopt($curlResource, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curlResource, CURLOPT_HTTPHEADER, $this->getHeaders($data));
        curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, 1);

        return $this->getCurlResult($curlResource);*/

        return $this->sendPost($methodUrl, $data);
    }

    /**
     * @param $uuid
     * @return mixed
     * @throws \Exception
     */
    public function messageInfo($uuid)
    {
        $methodUrl = 'message/' . $uuid;

        return $this->sendGet($methodUrl);
    }

    /**
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function senderNameList($data = [])
    {
        $methodUrl = 'sender-name';

        return $this->sendGet($methodUrl, $data);
    }

    /**
     * @param $id
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function getSenderName($id, $data = [])
    {
        $methodUrl = 'sender-name/' . $id;

        return $this->sendGet($methodUrl, $data);
    }

    /**
     * @param array $data
     * @param string $methodUrl
     * @return mixed
     * @throws \Exception
     */
    public function createDispatch($data = [], $methodUrl = 'dispatch')
    {
        return $this->sendPost($methodUrl, $data);
    }

    /**
     * @param $id
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function getDispatch($id, $data = [])
    {
        $methodUrl = 'dispatch/' . $id;

        return $this->sendGet($methodUrl, $data);
    }

    /**
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function getDispatchList($data = [])
    {
        $methodUrl = 'dispatch';

        return $this->sendGet($methodUrl, $data);
    }

    /**
     * @param $id
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function pauseDispatch($id, $data = [])
    {
        $methodUrl = 'dispatch/' . $id . '/pause';

        return $this->sendGet($methodUrl, $data);
    }

    /**
     * @param $id
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function resumeDispatch($id, $data = [])
    {
        $methodUrl = 'dispatch/' . $id . '/resume';

        return $this->sendGet($methodUrl, $data);
    }

    /**
     * @param $id
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function cancelDispatch($id, $data = [])
    {
        $methodUrl = 'dispatch/' . $id . '/cancel';

        return $this->sendGet($methodUrl, $data);
    }

    /**
     * @param $url
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    protected function sendGet($url, $data = [])
    {
        $curlResource = curl_init();
        $vars = http_build_query($data, '', '&');
        curl_setopt($curlResource, CURLOPT_URL, $this->apiUrl . "/" . $url . "?$vars");
        curl_setopt($curlResource, CURLOPT_HTTPHEADER, $this->getHeaders($data));
        curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, 1);

        return $this->getCurlResult($curlResource);
    }

    /**
     * @param $url
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    protected function sendPost($url, $data = [])
    {
        $curlResource = curl_init();
        curl_setopt($curlResource, CURLOPT_URL, $this->apiUrl . "/" . $url);
        curl_setopt($curlResource, CURLOPT_POST, 1);
        curl_setopt($curlResource, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curlResource, CURLOPT_HTTPHEADER, $this->getHeaders($data));
        curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, 1);

        return $this->getCurlResult($curlResource);
    }

    /**
     * @param $url
     * @return mixed
     * @throws \Exception
     */
    protected function sendDelete($url)
    {
        $curlResource = curl_init();
        curl_setopt($curlResource, CURLOPT_URL, $this->apiUrl . "/" . $url);
        curl_setopt($curlResource, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curlResource, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, true);

        return $this->getCurlResult($curlResource);
    }

    /**
     * @param $url
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    protected function postFile($url, $name)
    {
        $curlResource = curl_init();
        curl_setopt($curlResource, CURLOPT_URL, $this->apiUrl . "/" . $url);
        curl_setopt($curlResource, CURLOPT_POST, true);
        curl_setopt($curlResource, CURLOPT_POSTFIELDS, ['file' => new \CURLFile($name)]);
        curl_setopt($curlResource, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, true);

        return $this->getCurlResult($curlResource);
    }

    /**
     * @param $curlResource
     * @return mixed
     * @throws \Exception
     */
    protected function getCurlResult($curlResource)
    {
        $response = curl_exec($curlResource);
        $info = curl_getinfo($curlResource);
        curl_close($curlResource);
        $responseArray = json_decode($response, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \Exception('Error response format', $info['http_code']);
        }

        if ($info['http_code'] != 200) {
            throw new \Exception($responseArray['error_message'], $info['http_code']);
        }

        return $responseArray;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getHeaders($data = [])
    {
        ksort($data);
        reset($data);
        $ts = microtime() . rand(0, 10000);

        return [
            'login: ' . $this->login,
            'ts: ' . $ts,
            'sig: ' . md5(implode('', $data) . $ts . $this->apiKey),
        ];
    }
}
