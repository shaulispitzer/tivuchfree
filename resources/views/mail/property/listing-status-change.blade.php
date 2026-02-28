<x-mail::message>
# @if($action === 'deleted')
{{ __('mail.listing_status_change.title_deleted') }}
@else
{{ __('mail.listing_status_change.title_marked_as_taken') }}
@endif

{{ __('mail.listing_status_change.hi', ['name' => $recipientName]) }}

@if($action === 'deleted')
{!! $method === 'automatically' ? __('mail.listing_status_change.deleted_automatically', ['address' => $propertyAddress]) : __('mail.listing_status_change.deleted_manually', ['address' => $propertyAddress]) !!}
@else
{!! $method === 'automatically' ? __('mail.listing_status_change.taken_automatically', ['address' => $propertyAddress]) : __('mail.listing_status_change.taken_manually', ['address' => $propertyAddress]) !!}
@endif

@if($action === 'marked_as_taken')
{{ __('mail.listing_status_change.repost') }}
@endif

<x-mail::button :url="route('my-properties.index')">
{{ __('mail.listing_status_change.button') }}
</x-mail::button>

{{ __('mail.listing_status_change.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
