@component('mail::message')

# Transfer Notification

Hello {{ $user->name }},

A **{{ ucfirst($transfer->type) }}** transfer has been processed on your account.

@component('mail::panel')
**Transfer Details:**
- Type: {{ ucfirst($transfer->type) }}
- Amount: ${{ number_format($transfer->amount, 2) }}
- Status: {{ ucfirst($transfer->status) }}
- Created: {{ $transfer->created_at->format('M d, Y H:i A') }}
@if($transfer->description)
- Description: {{ $transfer->description }}
@endif
@endcomponent

If you did not authorize this transfer or have any questions, please contact our support team immediately.

Thanks,
{{ config('app.name') }}

@endcomponent
