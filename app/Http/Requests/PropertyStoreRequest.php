<?php

namespace App\Http\Requests;

use App\Enums\Neighbourhood;
use App\Enums\PropertyAccess;
use App\Enums\PropertyAirConditioning;
use App\Enums\PropertyApartmentCondition;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyKitchenDiningRoom;
use App\Enums\PropertyLeaseType;
use App\Enums\PropertyPorchGarden;
use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Property::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'neighbourhood' => ['nullable', Rule::enum(Neighbourhood::class)],
            'price' => ['nullable', 'numeric', 'min:0'],
            'street' => ['required', 'string', 'max:255'],
            'building_number' => ['nullable', 'string', 'max:50'],
            'floor' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::enum(PropertyLeaseType::class)],
            'available_from' => ['required', 'date'],
            'available_to' => [
                'nullable',
                'date',
                'after_or_equal:available_from',
                'required_if:type,'.PropertyLeaseType::MediumTerm->value,
            ],
            'bedrooms' => ['required', 'integer', 'min:0'],
            'square_meter' => ['nullable', 'integer', 'min:0'],
            'furnished' => ['required', Rule::enum(PropertyFurnished::class)],
            'taken' => ['boolean'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'access' => ['nullable', Rule::enum(PropertyAccess::class)],
            'kitchen_dining_room' => ['nullable', Rule::enum(PropertyKitchenDiningRoom::class)],
            'porch_garden' => ['nullable', Rule::enum(PropertyPorchGarden::class)],
            'succah_porch' => ['boolean'],
            'air_conditioning' => ['nullable', Rule::enum(PropertyAirConditioning::class)],
            'apartment_condition' => ['nullable', Rule::enum(PropertyApartmentCondition::class)],
            'additional_info' => ['nullable', 'string'],
            'has_dud_shemesh' => ['boolean'],
            'has_machsan' => ['boolean'],
            'has_parking_spot' => ['boolean'],
            'main_image' => ['nullable', 'image', 'max:5120'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['image', 'max:5120'],
        ];
    }
}
