<?php

namespace TrueCore\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;

class Post implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $_login = '';
    private $_password = '';
    private $_url = '';
    private $_data = [];
    private $_headers = [];

    /**
     * Post constructor.
     * Create a new job instance.
     *
     * @param string $login
     * @param string $password
     * @param string $url
     * @param array $data
     */
    public function __construct(string $login, string $password, string $url, array $data)
    {
        $this->_login = $login;
        $this->_password = $password;
        $this->_url = $url;
        $this->_data = $data;

        $this->setHeaders();

        $this->queue = 'post';
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return $this->_data;
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return $this->_url;
    }

    protected function setHeaders()
    {
        $data = $this->_data;
        ksort($data);
        reset($data);
        $ts = microtime() . rand(0, 10000);

        $this->_headers = [
            'login: ' . $this->_login,
            'ts: ' . $ts,
            'sig: ' . md5(implode('', $data) . $ts . $this->_password),
        ];
    }

    protected function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
//        $curl = new Curl;
//        $curl->makePost($this->getUrl(), $this->getData(), $this->getHeaders());
//        $curl->exec();

        Http::withHeaders($this->getHeaders())->post($this->getUrl(),$this->getData());

//        $response = $curl->exec();
//
//        $mc = new MultiCurl();
//
//        \Hhxsv5\PhpMultiCurl\Curl::to($this->_url)->withData($this->_data)->withHeaders($this->getHeaders($this->_data))
//            ->asJson(true)->post();
    }
}
