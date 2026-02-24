<x-mail::message>
# New Contact Message

**Subject:** {{ $contactSubject }}

**From:** {{ $email }}

**About a Dira:** {{ $isAboutDira ? 'Yes' : 'No' }}

@if($isAboutDira && $propertyId)
**Dira ID:** {{ $propertyId }}
@endif

**Message:**

{{ $contactMessage }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
