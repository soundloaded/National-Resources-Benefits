@component('mail::message')

# Hello {{ $user->name }},

{{ $body }}

Thanks,
{{ config('app.name') }}

@endcomponent
