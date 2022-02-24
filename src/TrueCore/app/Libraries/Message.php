<?php

namespace TrueCore\App\Libraries;

use TrueCore\App\Jobs\Post;
use Illuminate\Support\Facades\Http;

class Message
{
    const SMS_TYPE = 'sms';
    const VIBER_TYPE = 'viber';
    const RESEND_TYPE = 'viber,sms';

    const VALIDITY_PERIOD_MIN = 60;
    const VALIDITY_PERIOD_MAX = 1440 * 60;

    private $apiUrl;
    protected $apiLogin;
    protected $apiKey;

    public function __construct()
    {
        $this->apiLogin = config('sms.apiLogin');
        $this->apiKey = config('sms.apiKey');
        $this->apiUrl = config('sms.apiUrl');
    }

    /**
     * @param array|string $to
     * @param string $text
     *
     * @return bool
     */
    public function sendSms($to, string $text): bool
    {
//        $methodUrl = 'message';

        if (!(is_array($to) || is_string($to))) {
            throw new \InvalidArgumentException('Invalid parameter: to');
        }

        if (is_string($to)) {
            $to = [$to];
        }

        $data = [
            'login'  => $this->apiLogin,
            'psw'    => $this->apiKey,
            'phones' => implode(',', $to),
            'mes'    => $text,
        ];

        Http::asForm()->post($this->apiUrl, $data);

//        Post::dispatch(config('sms.apiLogin'), config('sms.apiKey'), $this->apiUrl . '/' . $methodUrl, $data);

        return true;

//        $response = $this->sendPost($methodUrl, $data);
//
//        return (array_key_exists('success', $response) && $response['success'] === true);
    }

    /**
     * @param $to
     * @param $text
     * @param $from
     * @param $btnText
     * @param $btnUrl
     * @param $imageUrl
     *
     * @return mixed
     */
//    public function sendViber($to, $text, $from, $btnText, $btnUrl, $imageUrl)
//    {
//        $methodUrl = 'message';
//
//        $to = is_array($to) ? $to : [$to];
//        $data = [
//            'to'             => implode(',', $to),
//            'text'           => $text,
//            'from'           => $from,
//            'route'          => self::VIBER_TYPE,
//            'viber.btnText'  => $btnText,
//            'viber.btnUrl'   => $btnUrl,
//            'viber.imageUrl' => $imageUrl,
//        ];
//
//        return $this->sendPost($methodUrl, $data);
//    }

//    /**
//     * @param string $url
//     * @param array $data
//     * @return mixed
//     */
//    protected function sendGet(string $url, $data = [])
//    {
//        return Curl::to($this->apiUrl . '/' . $url)
//            ->withData($data)->withHeader($this->getHeaders($data))->asJson(true)->get();
//    }

//    /**
//     * @param string $url
//     * @param array $data
//     * @return mixed
//     */
//    protected function sendPost(string $url, $data = [])
//    {
//        return Curl::to($this->apiUrl . '/' . $url)
//            ->withData($data)->withHeaders($this->getHeaders($data))->asJson(true)->post();
//    }

//    protected function getHeaders($data = [])
//    {
//        ksort($data);
//        reset($data);
//        $ts = microtime() . rand(0, 10000);
//
//        return [
//            'login: ' . $this->apiLogin,
//            'ts: ' . $ts,
//            'sig: ' . md5(implode('', $data) . $ts . $this->apiKey),
//        ];
//    }
}
