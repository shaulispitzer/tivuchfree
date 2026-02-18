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
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $property = $this->route('property');

        return $this->user()?->can('update', $property) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'neighbourhoods' => ['required', 'array', 'min:1', 'max:3'],
            'neighbourhoods.*' => ['required', Rule::enum(Neighbourhood::class), 'distinct'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'street' => ['required', 'string', 'max:255'],
            'building_number' => ['nullable', 'string', 'max:50'],
            'floor' => ['required', 'numeric', 'decimal:0,1'],
            'type' => ['required', Rule::enum(PropertyLeaseType::class)],
            'available_from' => ['required', 'date'],
            'available_to' => [
                'nullable',
                'date',
                'after_or_equal:available_from',
                'required_if:type,'.PropertyLeaseType::MediumTerm->value,
            ],
            'bedrooms' => ['required', 'numeric', 'decimal:0,1', 'min:0'],
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
            'temp_upload_id' => ['nullable', 'integer', Rule::exists('temp_uploads', 'id')],
            'image_media_ids' => ['nullable', 'array', 'max:6'],
            'image_media_ids.*' => ['integer'],
            'main_image_media_id' => ['nullable', 'integer'],
            'main_image' => ['nullable', 'image', 'max:5120'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['image', 'max:5120'],
        ];
    }
}
