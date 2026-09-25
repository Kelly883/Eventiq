@component('mail::message')
# Refund Status Updated

Your refund request status has been updated.

**Reference Number:** {{ $refundRequest->reference_number }}
**Status:** {{ ucfirst($refundRequest->status) }}

 @if($refundRequest->status === 'completed')
Your refund of ${{ number_format($refundRequest->refund_amount, 2) }} has been processed.
@elseif($refundRequest->status === 'rejected')
Your refund request has been rejected. Reason: {{ $refundRequest->rejection_reason }}
@endif

@component('mail::button', ['url' => config('app.url') . '/refunds/' . $refundRequest->id])
View Details
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
