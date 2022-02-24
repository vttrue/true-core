<?php

namespace TrueCore\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use TrueCore\App\Libraries\Notification\Notification as libNotification;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int    $notificationHandler = 1;
    private array  $to                  = [];
    private string $content             = '';
    private array  $providerData        = [];

    /**
     * SendNotification constructor.
     *
     * @param int    $notificationHandler
     * @param array  $to
     * @param string $content
     * @param array  $providerData
     */
    public function __construct(int $notificationHandler, array $to, string $content, array $providerData = [])
    {
        $this->notificationHandler = $notificationHandler;
        $this->to = $to;
        $this->content = $content;
        $this->providerData = $providerData;

        $this->queue = 'notification';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new libNotification($this->providerData))->send($this->notificationHandler, ['to' => $this->to, 'content' => $this->content]);
    }
}
