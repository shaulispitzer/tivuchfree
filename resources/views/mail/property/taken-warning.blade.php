<x-mail::message>
# {{ __('mail.taken_warning.title') }}

{!! __('mail.taken_warning.body', ['address' => $property->street . ($property->building_number ? ' ' . $property->building_number : ''), 'days' => $daysUntilTaken]) !!}

{{ __('mail.taken_warning.repost') }}

<x-mail::button :url="route('my-properties.index')">
{{ __('mail.taken_warning.button') }}
</x-mail::button>

{{ __('mail.taken_warning.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
