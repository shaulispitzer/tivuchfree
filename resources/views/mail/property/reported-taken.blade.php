<x-mail::message>
# {{ __('mail.reported_taken.title') }}

{{ __('mail.reported_taken.hi', ['name' => $property->user->name ?? '']) }}

{!! __('mail.reported_taken.body', ['address' => $property->street . ($property->building_number ? ' ' . $property->building_number : ''), 'days' => $daysToResolve]) !!}

{{ __('mail.reported_taken.call_to_action') }}

<x-mail::button :url="route('properties.confirm-reported-taken', $property)" color="error">
{{ __('mail.reported_taken.button_confirm_taken') }}
</x-mail::button>

<x-mail::button :url="route('properties.keep-available', $property)" color="success">
{{ __('mail.reported_taken.button_not_taken_yet') }}
</x-mail::button>

{{ __('mail.reported_taken.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
