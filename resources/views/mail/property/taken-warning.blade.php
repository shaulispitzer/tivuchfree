<x-mail::message>
# Your listing will be marked as taken soon

Your property listing at **{{ $property->street }}{{ $property->building_number ? ' ' . $property->building_number : '' }}** will be automatically marked as **taken** in {{ $daysUntilTaken }} days.

If your property is still available, you can repost it from the "My Properties" page after it has been marked as taken.

<x-mail::button :url="route('my-properties.index')">
View My Properties
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
