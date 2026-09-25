<?php

namespace App\Mail;

use App\Features\Refunds\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RefundRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RefundRequest $refundRequest,
    ) {
    }

    public function build()
    {
        return $this->subject('Refund Request Received - ' . $this->refundRequest->reference_number)
            ->markdown('emails.refunds.requested', [
                'refundRequest' => $this->refundRequest,
            ]);
    }
}
