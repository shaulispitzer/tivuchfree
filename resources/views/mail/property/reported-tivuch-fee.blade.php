<x-mail::message>
# {{ __('mail.reported_tivuch_fee.title') }}

{{ __('mail.reported_tivuch_fee.body', ['address' => $property->street . ($property->building_number ? ' ' . $property->building_number : ''), 'id' => $property->id]) }}

<x-mail::button :url="route('admin.properties.edit', $property)">
{{ __('mail.reported_tivuch_fee.button') }}
</x-mail::button>

{{ __('mail.reported_tivuch_fee.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
