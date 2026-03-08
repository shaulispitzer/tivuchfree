<x-mail::message>
# Weekly Temp Upload Cleanup

The scheduled cleanup ran successfully. Here's what was removed:

| | Count |
|---|---|
| Abandoned upload sessions | {{ $uploadCount }} |
| Associated image files | {{ $mediaCount }} |

These were temporary uploads that were created more than 24 hours ago and never attached to a property listing (e.g. a user started filling out the property form, uploaded images, then navigated away).

@if($uploadCount === 0)
**Nothing to clean up this week** — no stale uploads were found.
@else
**{{ $uploadCount }} {{ Str::plural('session', $uploadCount) }}** and **{{ $mediaCount }} {{ Str::plural('image', $mediaCount) }}** have been removed from the database and storage.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
