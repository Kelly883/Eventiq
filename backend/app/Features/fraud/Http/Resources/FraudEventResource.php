<?php

namespace App\Features\Fraud\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FraudEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'orderId' => $this->resource->order_id,
            'userId' => $this->resource->user_id,
            'userEmail' => $this->resource->user_email,
            'ticketId' => $this->resource->ticket_id,
            'eventId' => $this->resource->event_id,
            'eventType' => $this->resource->fraud_type,
            'riskScore' => (float) $this->resource->risk_score,
            'riskLevel' => $this->resource->risk_level,
            'detectionMethod' => $this->resource->detection_method,
            'fraudFactors' => $this->resource->fraud_factors,
            'paymentDetails' => $this->resource->payment_details,
            'velocityMetrics' => $this->resource->velocity_metrics,
            'deviceInfo' => $this->resource->device_info,
            'duplicateTicketInfo' => $this->resource->duplicate_ticket_info,
            'detectedAt' => $this->resource->detected_at,
            'firstCheckInAt' => $this->resource->first_check_in_at,
            'firstCheckInBy' => $this->resource->first_check_in_by,
            'secondCheckInAt' => $this->resource->second_check_in_at,
            'secondCheckInBy' => $this->resource->second_check_in_by,
            'status' => $this->resource->status,
            'reviewedBy' => $this->resource->reviewed_by,
            'reviewNotes' => $this->resource->review_notes,
            'reviewedAt' => $this->resource->reviewed_at,
            'notes' => $this->resource->notes,
            'sessionId' => $this->resource->session_id,
            'ipAddress' => $this->resource->ip_address,
            'cardFingerprint' => $this->resource->card_fingerprint,
            'amount' => $this->resource->amount !== null ? (float) $this->resource->amount : null,
            'currency' => $this->resource->currency,
            'gatewayResponseCode' => $this->resource->gateway_response_code,
            'automatedActionTaken' => $this->resource->automated_action_taken,
            'source' => $this->resource->source,
            'paymentIntentId' => $this->resource->payment_intent_id,
            'chargebackFlag' => (bool) $this->resource->chargeback_flag,
            'authenticationMethod' => $this->resource->authentication_method,
            'cardCountry' => $this->resource->card_country,
            'deviceFingerprint' => $this->resource->device_fingerprint,
            'paymentMethod' => $this->resource->payment_method,
            'paymentGateway' => $this->resource->payment_gateway,
            'userOrdersLast24h' => $this->resource->user_orders_last_24h,
            'userSpendLast24h' => $this->resource->user_spend_last_24h !== null ? (float) $this->resource->user_spend_last_24h : null,
            'userAgent' => $this->resource->user_agent,
            'referrer' => $this->resource->referrer,
            'promoCode' => $this->resource->promo_code,
            'escalatedTo' => $this->resource->escalated_to,
            'escalatedAt' => $this->resource->escalated_at,
            'resolution' => $this->resource->resolution,
            'evidenceSnapshot' => $this->resource->evidence_snapshot,
            'isArchived' => (bool) $this->resource->is_archived,
            'archivedAt' => $this->resource->archived_at,
            'orderTotal' => $this->resource->order_total !== null ? (float) $this->resource->order_total : null,
            'ticketQuantity' => $this->resource->ticket_quantity,
            'billingCountry' => $this->resource->billing_country,
            'billingZip' => $this->resource->billing_zip,
            'shippingBillingMatch' => (bool) $this->resource->shipping_billing_match,
            'orderStatus' => $this->resource->order_status,
            'deviceType' => $this->resource->device_type,
            'proxyVpnDetected' => (bool) $this->resource->proxy_vpn_detected,
            'ipReputationScore' => $this->resource->ip_reputation_score,
            'accountAgeDays' => $this->resource->account_age_days,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }
}
