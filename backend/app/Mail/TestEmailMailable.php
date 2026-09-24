<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmailMailable extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailSubject;

    public function __construct(
        public string $htmlContent,
        public string $recipientEmail,
        ?string $subject = null,
    ) {
        $this->emailSubject = $subject ?? 'Test Email';
    }

    public function build()
    {
        return $this->subject($this->emailSubject)
            ->html($this->htmlContent);
    }
}
