<?php

namespace App\Features\EmailNotifications\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmailMailable extends Mailable
{
    use Queueable, SerializesModels;
    public function build()
    {
        return $this->view('emails.test');
    }
}
