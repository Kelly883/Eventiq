<?php

namespace App\Features\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'organizerId' => $this->organizer_id,
            'orderId' => $this->order_id,
            'eventId' => $this->event_id,
            'ticketId' => $this->ticket_id,
            'reference' => $this->reference,
            'gateway' => $this->gateway,
            'gatewayTransactionId' => $this->gateway_transaction_id,
            'gatewayReference' => $this->gateway_reference,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'paymentType' => $this->payment_channel,
            'paymentChannel' => $this->payment_channel,
            'customerEmail' => $this->customer_email,
            'customerCode' => $this->customer_code,
            'authorizationCode' => $this->authorization_code,
            'authorizationType' => $this->authorization_type,
            'fees' => $this->fees !== null ? (float) $this->fees : null,
            'netAmount' => $this->net_amount !== null ? (float) $this->net_amount : null,
            'refundedAmount' => $this->refunded_amount !== null ? (float) $this->refunded_amount : null,
            'refundReference' => $this->refund_reference,
            'isFullyRefunded' => (bool) $this->is_fully_refunded,
            'paidAt' => $this->paid_at,
            'lastError' => $this->last_error,
            'attempts' => $this->attempts,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
