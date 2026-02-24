<x-mail::message>
# @if($action === 'deleted')
Your listing was deleted
@else
Your listing was marked as taken
@endif

Hi {{ $recipientName }},

Your property listing at **{{ $propertyAddress }}** was {{ $action === 'deleted' ? 'deleted' : 'marked as taken' }} {{ $method === 'automatically' ? 'automatically by our system (e.g. after being taken for a certain period).' : 'manually.' }}

@if($action === 'marked_as_taken')
If your property is still available, you can repost it from the "My Properties" page.
@endif

<x-mail::button :url="route('my-properties.index')">
View My Properties
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
