<x-mail::message>
Hi, {{ $user->name }}!

Your property was listed successfully.
<x-mail::button :url="route('my-properties.index')">
View My Properties
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}

</x-mail::message>
