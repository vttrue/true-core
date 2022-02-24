<?php

namespace TrueCore\App\Extended;

use Illuminate\Support\Facades\Log;

class Mail extends \Illuminate\Support\Facades\Mail
{
    /**
     * @param array|\Illuminate\Contracts\Mail\Mailable|string $view
     * @param string|null $queue
     * @return mixed
     */
    public static function queue($view, $queue = null)
    {
        try {
            return config('mail.send_via_queue') ? parent::queue($view, $queue) : static::send($view);
        } catch (\Throwable $e) {
            $errorText =
                '----------------------------'
                . "\n" . 'Error:'
                . "\n" . 'File: ' . $e->getFile()
                . "\n" . 'Line: ' . $e->getLine()
                . "\n" . 'Message: ' . $e->getMessage()
                . "\n";
            Log::channel('email')->info($errorText);
        }
    }
}
