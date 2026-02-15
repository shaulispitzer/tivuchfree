<?php

namespace App\Http\Controllers;

use App\Data\Forms\PropertyFormData;
use App\Data\PropertyData;
use App\Data\PropertyFormOptionsData;
use App\Enums\Neighbourhood;
use App\Enums\PropertyAccess;
use App\Enums\PropertyAirConditioning;
use App\Enums\PropertyApartmentCondition;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyKitchenDiningRoom;
use App\Enums\PropertyLeaseType;
use App\Enums\PropertyPorchGarden;
use App\Http\Requests\PropertyUpdateRequest;
use App\Models\Property;
use App\Models\Street;
use App\Models\TempUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = Validator::make($request->query(), [
            'neighbourhood' => ['nullable', Rule::enum(Neighbourhood::class)],
            'availability' => ['nullable', Rule::in(['all', 'available'])],
            'bedrooms_min' => ['nullable', 'numeric', 'min:0'],
            'bedrooms_max' => ['nullable', 'numeric', 'min:0'],
            'furnished' => ['nullable', Rule::enum(PropertyFurnished::class)],
            'type' => ['nullable', Rule::enum(PropertyLeaseType::class)],
            'available_from' => ['nullable', 'date'],
            'available_to' => ['nullable', 'date', 'after_or_equal:available_from'],
            'sort' => ['nullable', Rule::in(['price_asc', 'price_desc', 'newest', 'oldest'])],
        ])->validate();

        $bedroomsMin = isset($validated['bedrooms_min']) ? (float) $validated['bedrooms_min'] : null;
        $bedroomsMax = isset($validated['bedrooms_max']) ? (float) $validated['bedrooms_max'] : null;
        $availability = $validated['availability'] ?? 'all';
        $type = $validated['type'] ?? null;
        $sort = $validated['sort'] ?? 'newest';

        $propertiesQuery = Property::query()
            ->with(['media'])
            ->when(isset($validated['neighbourhood']), function ($query) use ($validated) {
                $query->whereJsonContains('neighbourhoods', $validated['neighbourhood']);
            })
            ->when($availability === 'available', function ($query) {
                $query->where('taken', false);
            })
            ->when(isset($validated['furnished']), function ($query) use ($validated) {
                $query->where('furnished', $validated['furnished']);
            })
            ->when($type !== null, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when(
                $bedroomsMin !== null || $bedroomsMax !== null,
                function ($query) use ($bedroomsMin, $bedroomsMax) {
                    if ($bedroomsMin !== null && $bedroomsMax !== null) {
                        $query->whereBetween('bedrooms', [
                            min($bedroomsMin, $bedroomsMax),
                            max($bedroomsMin, $bedroomsMax),
                        ]);

                        return;
                    }

                    $query->where('bedrooms', $bedroomsMin ?? $bedroomsMax);
                },
            )
            ->when($type === PropertyLeaseType::MediumTerm->value, function ($query) use ($validated) {
                if (isset($validated['available_from'])) {
                    $query->whereDate('available_from', '>=', $validated['available_from']);
                }

                if (isset($validated['available_to'])) {
                    $query->whereDate('available_to', '<=', $validated['available_to']);
                }
            });

        $propertiesQuery = match ($sort) {
            'price_asc' => $propertiesQuery->orderBy('price')->orderByDesc('created_at'),
            'price_desc' => $propertiesQuery->orderByDesc('price')->orderByDesc('created_at'),
            'oldest' => $propertiesQuery->oldest(),
            default => $propertiesQuery->latest(),
        };

        $properties = $propertiesQuery
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Property $property) => (
                PropertyData::fromModel($property)->except('user', 'user_id')->toArray()
            ));

        return Inertia::render('properties/Index', [
            'properties' => $properties,
            'can_create' => Auth::check(),
            'filters' => [
                'neighbourhood' => $validated['neighbourhood'] ?? null,
                'availability' => $availability,
                'bedrooms_min' => $bedroomsMin,
                'bedrooms_max' => $bedroomsMax,
                'furnished' => $validated['furnished'] ?? null,
                'type' => $type,
                'available_from' => $validated['available_from'] ?? null,
                'available_to' => $validated['available_to'] ?? null,
                'sort' => $sort,
            ],
            'neighbourhood_options' => array_map(
                fn (Neighbourhood $neighbourhood) => $neighbourhood->value,
                Neighbourhood::cases(),
            ),
            'furnished_options' => array_map(
                fn (PropertyFurnished $furnished) => [
                    'value' => $furnished->value,
                    'label' => $furnished->label(),
                ],
                PropertyFurnished::cases(),
            ),
            'type_options' => array_map(
                fn (PropertyLeaseType $leaseType) => [
                    'value' => $leaseType->value,
                    'label' => $leaseType->label(),
                ],
                PropertyLeaseType::cases(),
            ),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Property::class);

        return Inertia::render('properties/Create', [
            'options' => $this->formOptions(),
        ]);
    }

    public function streets(Request $request): JsonResponse
    {
        $this->authorize('create', Property::class);

        $validated = Validator::make($request->query(), [
            'neighbourhoods' => ['nullable', 'array', 'min:1', 'max:3'],
            'neighbourhoods.*' => ['required', Rule::enum(Neighbourhood::class), 'distinct'],
        ])->validate();

        return response()->json([
            'streets' => $this->streetOptions($validated['neighbourhoods'] ?? []),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PropertyFormData $data, Request $request)
    {

        Validator::make([
            'neighbourhoods' => $data->neighbourhoods,
            'street' => $data->street,
            'floor' => $data->floor,
        ], [
            'neighbourhoods' => ['required', 'array', 'min:1', 'max:3'],
            'neighbourhoods.*' => ['required', Rule::enum(Neighbourhood::class), 'distinct'],
            'street' => ['required', 'integer', 'exists:streets,id'],
            'floor' => ['required', 'numeric', 'decimal:0,1', 'min:0'],
        ])->validate();

        $streetInHebrew = Street::query()
            ->find($data->street)
            ?->getTranslation('name', 'he');

        if (! is_string($streetInHebrew) || $streetInHebrew === '') {
            throw ValidationException::withMessages([
                'street' => 'Please select a valid street.',
            ]);
        }

        if ($data->type === PropertyLeaseType::LongTerm) {
            $data->available_to = null;
        }
        $property = DB::transaction(function () use ($data, $request, $streetInHebrew) {
            $property = Property::create([
                'user_id' => $request->user()->id,
                'neighbourhoods' => array_values(array_unique($data->neighbourhoods)),
                'building_number' => $data->building_number,
                'street' => $streetInHebrew,
                'floor' => $data->floor,
                'type' => $data->type,
                'available_from' => $data->available_from,
                'available_to' => $data->available_to,
                'bedrooms' => $data->bedrooms,
                'furnished' => $data->furnished,
            ]);

            $mediaIds = $data->image_media_ids ? array_values(array_unique($data->image_media_ids)) : [];

            if (! $data->temp_upload_id || $mediaIds === []) {
                return $property;
            }

            if (count($mediaIds) > 6) {
                throw ValidationException::withMessages([
                    'image_media_ids' => 'You can upload up to 6 images.',
                ]);
            }

            $tempUpload = TempUpload::query()
                ->whereKey($data->temp_upload_id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (! $tempUpload) {
                throw ValidationException::withMessages([
                    'temp_upload_id' => 'Invalid upload session.',
                ]);
            }

            $mediaItems = $tempUpload->media()
                ->where('collection_name', 'images')
                ->whereIn('id', $mediaIds)
                ->get()
                ->keyBy('id');

            if ($mediaItems->count() !== count($mediaIds)) {
                throw ValidationException::withMessages([
                    'image_media_ids' => 'One or more images are missing.',
                ]);
            }

            $mainId = $data->main_image_media_id ?? $mediaIds[0];

            if (! in_array($mainId, $mediaIds, true)) {
                throw ValidationException::withMessages([
                    'main_image_media_id' => 'Main image must be one of the uploaded images.',
                ]);
            }

            $mainMedia = $mediaItems->get($mainId);

            if (! $mainMedia) {
                throw ValidationException::withMessages([
                    'main_image_media_id' => 'Main image is missing.',
                ]);
            }

            $mainMedia->move($property, 'main_image');

            $order = 1;
            foreach ($mediaIds as $mediaId) {
                if ((int) $mediaId === (int) $mainId) {
                    continue;
                }

                $media = $mediaItems->get($mediaId);

                if (! $media) {
                    continue;
                }

                $moved = $media->move($property, 'images');

                $moved->update([
                    'order_column' => $order,
                ]);

                $order++;
            }

            $tempUpload->delete();

            return $property;
        });

        return redirect()->route('properties.edit', $property)->success('Property created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function edit(Property $property)
    {
        $this->authorize('update', $property);

        $property->loadMissing(['user', 'media']);

        return Inertia::render('properties/Edit', [
            'property' => PropertyData::fromModel($property),
            'options' => $this->formOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PropertyUpdateRequest $request, Property $property)
    {
        $this->authorize('update', $property);

        $data = $request->validated();

        if ($data['type'] === PropertyLeaseType::LongTerm->value) {
            $data['available_to'] = null;
        }

        $property->update(Arr::except($data, ['main_image', 'images']));

        if ($request->hasFile('main_image')) {
            $property->clearMediaCollection('main_image');
            $property->addMediaFromRequest('main_image')->toMediaCollection('main_image');
        }

        if ($request->hasFile('images')) {
            $property->clearMediaCollection('images');
            foreach ($request->file('images', []) as $image) {
                $property->addMedia($image)->toMediaCollection('images');
            }
        }

        return redirect()->route('properties.edit', $property);
    }

    protected function formOptions(): PropertyFormOptionsData
    {
        return PropertyFormOptionsData::fromEnums(
            Neighbourhood::cases(),
            PropertyLeaseType::cases(),
            PropertyFurnished::cases(),
            PropertyAccess::cases(),
            PropertyKitchenDiningRoom::cases(),
            PropertyPorchGarden::cases(),
            PropertyAirConditioning::cases(),
            PropertyApartmentCondition::cases(),
        );
    }

    /**
     * @param  array<int, string>  $neighbourhoods
     * @return array<int, array{id: int, name: string}>
     */
    protected function streetOptions(array $neighbourhoods = []): array
    {
        if ($neighbourhoods === []) {
            return [];
        }

        $locale = app()->getLocale();

        return Street::query()
            ->whereIn('neighbourhood', array_values(array_unique($neighbourhoods)))
            ->get()
            ->map(function (Street $street) use ($locale): array {
                $localizedName = $street->getTranslation('name', $locale, false);

                if (! is_string($localizedName) || $localizedName === '') {
                    $localizedName = $street->getTranslation('name', 'he');
                }

                return [
                    'id' => $street->id,
                    'name' => $localizedName,
                ];
            })
            ->unique('name')
            ->sortBy('name')
            ->values()
            ->all();
    }
}
