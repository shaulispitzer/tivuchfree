<x-mail::message>
{{ __('mail.successfully_listed.hi', ['name' => $user->name]) }}

{{ __('mail.successfully_listed.body') }}
<x-mail::button :url="route('my-properties.index')">
{{ __('mail.successfully_listed.button') }}
</x-mail::button>

{{ __('mail.successfully_listed.thanks') }}<br>
{{ config('app.name') }}

</x-mail::message>
