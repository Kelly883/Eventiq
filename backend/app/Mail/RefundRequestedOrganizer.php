<?php

namespace App\Mail;

use App\Features\Refunds\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RefundRequestedOrganizer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RefundRequest $refundRequest,
    ) {
    }

    public function build()
    {
        return $this->subject('New Refund Request - ' . $this->refundRequest->reference_number)
            ->markdown('emails.refunds.requested-organizer', [
                'refundRequest' => $this->refundRequest,
            ]);
    }
}
