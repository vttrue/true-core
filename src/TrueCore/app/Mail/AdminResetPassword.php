<?php

namespace TrueCore\App\Mail;

use \TrueCore\App\Libraries\Config;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AdminResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    protected $data = [];
    protected $swiftData = [];

    /**
     * Create a new message instance.
     *
     * @param array $data
     * @param array $swiftData
     *
     * @return void
     */
    public function __construct(array $data, array $swiftData)
    {
        $this->data = $data;
        $this->swiftData = $swiftData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->withSwiftMessage(function(\Swift_Message $message) {
            $message->setFrom(config('mail.from.address'), Config::getInstance()->get('senderName', 'email', config('mail.from.name')));
            $message
                ->setTo($this->swiftData['mailTo'], $this->swiftData['nameTo'])
                ->setSubject('Установка пароля администратора')
                ->setReplyTo(config('mail.replyTo.address', config('mail.from.address')), Config::getInstance()->get('senderName', 'email', config('mail.from.name')));
        });

        return $this->view('admin.mails.reset_password', $this->data);
    }
}
