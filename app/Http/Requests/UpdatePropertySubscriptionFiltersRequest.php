<?php

namespace App\Http\Requests;

use App\Enums\Neighbourhood;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyLeaseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertySubscriptionFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filters' => ['required', 'array'],
            'filters.neighbourhood' => ['nullable', 'string', Rule::enum(Neighbourhood::class)],
            'filters.hide_taken_properties' => ['nullable', 'boolean'],
            'filters.bedrooms_range' => ['nullable', 'array', 'size:2'],
            'filters.bedrooms_range.0' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'filters.bedrooms_range.1' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'filters.furnished' => ['nullable', 'string', Rule::enum(PropertyFurnished::class)],
            'filters.type' => ['nullable', 'string', Rule::enum(PropertyLeaseType::class)],
            'filters.available_from' => ['nullable', 'date'],
            'filters.available_to' => ['nullable', 'date', 'after_or_equal:filters.available_from'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filtersForStorage(): array
    {
        $filters = $this->validated('filters');
        $bedroomsRange = $filters['bedrooms_range'] ?? [1, 10];
        $min = isset($bedroomsRange[0]) ? round((float) $bedroomsRange[0] * 2) / 2 : 1;
        $max = isset($bedroomsRange[1]) ? round((float) $bedroomsRange[1] * 2) / 2 : 10;

        return [
            'neighbourhood' => $filters['neighbourhood'] ?? null,
            'availability' => ($filters['hide_taken_properties'] ?? false) ? 'available' : 'all',
            'bedrooms_min' => min($min, $max),
            'bedrooms_max' => max($min, $max),
            'furnished' => $filters['furnished'] ?? null,
            'type' => $filters['type'] ?? null,
            'available_from' => $filters['available_from'] ?? null,
            'available_to' => $filters['available_to'] ?? null,
        ];
    }
}
