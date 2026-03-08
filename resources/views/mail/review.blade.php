<x-mail::message>
# New Review Submitted

**From:** {{ $reviewerName }} ({{ $reviewerEmail }})

@if($reviewerRole)
**Role:** {{ $reviewerRole }}
@endif

**Message:**

{{ $reviewMessage }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
