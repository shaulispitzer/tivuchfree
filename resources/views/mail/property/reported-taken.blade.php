<x-mail::message>
# {{ __('mail.reported_taken.title') }}

{{ __('mail.reported_taken.hi', ['name' => $property->user->name ?? '']) }}

{!! __('mail.reported_taken.body', ['address' => $property->street . ($property->building_number ? ' ' . $property->building_number : ''), 'days' => $daysToResolve]) !!}

{{ __('mail.reported_taken.call_to_action') }}

<x-mail::button :url="$editUrl">
{{ __('mail.reported_taken.button') }}
</x-mail::button>

{{ __('mail.reported_taken.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
