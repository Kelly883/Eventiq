@component('mail::message')
# New Refund Request

A new refund request has been submitted for one of your events.

**Reference Number:** {{ $refundRequest->reference_number }}
**Amount:** ${{ number_format($refundRequest->refund_amount, 2) }}
**Reason:** {{ $refundRequest->reason }}

@component('mail::button', ['url' => config('app.url') . '/admin/refunds'])
Review Refund Request
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
