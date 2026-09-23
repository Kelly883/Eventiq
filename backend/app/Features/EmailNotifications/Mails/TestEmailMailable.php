<?php

namespace App\Features\EmailNotifications\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Generic Mailable used by the send-test endpoint. Accepts the rendered
 * HTML content of an email template and a subject line.
 */
class TestEmailMailable extends Mailable
{
    use Queueable, SerializesModels;

        public function __construct(
        public ?string $html = '',
        public ?string $subject = null,
    ) {
    }

    public function build()
    {
        $subject = '[TEST] ' . ($this->subject ?? '');

        if (view()->exists('emails.test')) {
            return $this->subject($subject)
                ->view('emails.test')
                ->with(['html' => $this->html ?? '']);
        }

        return $this->subject($subject)
            ->html($this->html ?? '');
    }
}
