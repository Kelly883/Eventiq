@component('mail::message')
# Refund Request Received

Your refund request has been received and is being processed.

**Reference Number:** {{ $refundRequest->reference_number }}
**Amount:** ${{ number_format($refundRequest->refund_amount, 2) }}
**Status:** {{ ucfirst($refundRequest->status) }}
**Expected Processing:** {{ $refundRequest->expected_processing_days }} business days

@component('mail::button', ['url' => config('app.url') . '/refunds/' . $refundRequest->id])
Track Refund Status
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
