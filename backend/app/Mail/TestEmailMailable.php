<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmailMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $htmlContent,
        public string $recipientEmail,
        public ?string $subject = null,
    ) {
    }

    public function build()
    {
        return $this->subject($this->subject ?? 'Test Email')
            ->html($this->htmlContent);
    }
}
