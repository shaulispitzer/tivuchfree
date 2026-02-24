<x-mail::message>
# New Property Match

A new property has been posted that matches your subscription filters.

**Price:** {{ $property->price ? '₪' . number_format($property->price) : 'not specified' }}

**Bedrooms:** {{ $property->bedrooms }}

**Street:** {{ $property->street }}

**Neighbourhoods:** {{ is_array($property->neighbourhoods) ? implode(', ', array_map(fn ($n) => \App\Enums\Neighbourhood::tryFrom($n)?->label() ?? $n, $property->neighbourhoods)) : '' }}

**Type:** {{ $property->type?->label() ?? 'not specified' }}

**Furnished:** {{ $property->furnished?->label() ?? 'not specified' }}

<x-mail::button :url="$propertyUrl">
View Property
</x-mail::button>

---

You can manage your subscription using the links below.

[Unsubscribe]({{ $unsubscribeUrl }})

[Update filters]({{ $updateFiltersUrl }})

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
