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
use App\Http\Requests\MarkPropertyAsTakenRequest;
use App\Models\Property;
use App\Models\Street;
use App\Models\TempUpload;
use App\Services\PropertyGeocoder;
use App\Services\PropertyStatRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PropertyController extends Controller
{
    public function __construct(protected PropertyGeocoder $propertyGeocoder) {}

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
            'view' => ['nullable', Rule::in(['list', 'map'])],
        ])->validate();

        $bedroomsMin = isset($validated['bedrooms_min']) ? (float) $validated['bedrooms_min'] : null;
        $bedroomsMax = isset($validated['bedrooms_max']) ? (float) $validated['bedrooms_max'] : null;
        $availability = $validated['availability'] ?? 'all';
        $type = $validated['type'] ?? null;
        $sort = $validated['sort'] ?? 'newest';
        $view = $validated['view'] ?? 'list';

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

        if ($view === 'map') {
            $properties = $propertiesQuery
                ->get()
                ->map(fn (Property $property) => (
                    PropertyData::fromModel($property)->only('id', 'price', 'bedrooms', 'lat', 'lon')->toArray()
                ))
                ->values()
                ->all();

            return Inertia::render('properties/Map', [
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
                    'view' => $view,
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

        $properties = $propertiesQuery
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Property $property) => (
                PropertyData::fromModel($property)->only('id', 'price', 'neighbourhoods', 'street', 'lat', 'lon', 'building_number', 'floor', 'type', 'available_from', 'available_to', 'bedrooms', 'square_meter', 'views', 'furnished', 'taken', 'image_urls', 'main_image_url')->toArray()
            ));

        return Inertia::render('properties/List', [
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
                'view' => $view,
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
            'floor' => ['required', 'numeric', 'decimal:0,1'],
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
        $coordinates = $this->propertyGeocoder->geocode(
            $streetInHebrew,
            $data->building_number !== null ? (string) $data->building_number : null,
        );

        $property = DB::transaction(function () use ($data, $request, $streetInHebrew, $coordinates) {
            $additionalInfo = array_filter([
                'en' => $data->additional_info_en,
                'he' => $data->additional_info_he,
            ], fn (?string $value): bool => is_string($value) && $value !== '');

            $property = Property::create([
                'user_id' => $request->user()->id,
                'contact_name' => $data->contact_name,
                'contact_phone' => $data->contact_phone,
                'neighbourhoods' => array_values(array_unique($data->neighbourhoods)),
                'building_number' => $data->building_number,
                'street' => $streetInHebrew,
                'lat' => $coordinates['lat'] ?? null,
                'lon' => $coordinates['lon'] ?? null,
                'floor' => $data->floor,
                'type' => $data->type,
                'available_from' => $data->available_from,
                'available_to' => $data->available_to,
                'bedrooms' => $data->bedrooms,
                'square_meter' => $data->square_meter,
                'furnished' => $data->furnished,
                'bathrooms' => $data->bathrooms,
                'access' => $data->access,
                'kitchen_dining_room' => $data->kitchen_dining_room,
                'porch_garden' => $data->porch_garden,
                'succah_porch' => $data->succah_porch,
                'air_conditioning' => $data->air_conditioning,
                'apartment_condition' => $data->apartment_condition,
                'additional_info' => $additionalInfo === [] ? null : $additionalInfo,
                'has_dud_shemesh' => $data->has_dud_shemesh,
                'has_machsan' => $data->has_machsan,
                'has_parking_spot' => $data->has_parking_spot,
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
            'lifecycle' => $this->propertyLifecycleInfo($property),
        ]);
    }

    /**
     * @return array{posted_at: string, taken: bool, next_action: string, next_action_date: string, days_remaining: int}
     */
    protected function propertyLifecycleInfo(Property $property): array
    {
        $today = now()->startOfDay();

        if ($property->taken && $property->taken_at) {
            $deletionDate = $property->taken_at->copy()->addDays(14)->startOfDay();
            $daysRemaining = max(0, (int) $today->diffInDays($deletionDate, false));

            return [
                'posted_at' => $property->created_at->toDateTimeString(),
                'taken' => true,
                'next_action' => 'deletion',
                'next_action_date' => $deletionDate->toDateTimeString(),
                'days_remaining' => $daysRemaining,
            ];
        }

        $takenDate = $property->created_at->copy()->addDays(30)->startOfDay();
        $daysRemaining = max(0, (int) $today->diffInDays($takenDate, false));

        return [
            'posted_at' => $property->created_at->toDateTimeString(),
            'taken' => false,
            'next_action' => 'marked_as_taken',
            'next_action_date' => $takenDate->toDateTimeString(),
            'days_remaining' => $daysRemaining,
        ];
    }

    public function myProperties(Request $request)
    {
        abort_if(! Auth::check(), 404);

        $properties = Property::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->get()
            ->map(fn (Property $property) => [
                'id' => $property->id,
                'street' => $property->street,
                'building_number' => $property->building_number,
                'floor' => $property->floor,
                'bedrooms' => $property->bedrooms,
                'type' => $property->type?->value,
                'taken' => $property->taken,
                'taken_at' => $property->taken_at?->toDateTimeString(),
                'created_at' => $property->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('properties/MyProperties', [
            'properties' => $properties,
        ]);
    }

    public function markAsTaken(MarkPropertyAsTakenRequest $request, Property $property, PropertyStatRecorder $propertyStatRecorder): RedirectResponse
    {
        if ($property->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $property->taken) {
            $property->update([
                'taken' => true,
                'taken_at' => now(),
            ]);
        }

        $validated = $request->validated();

        $propertyStatRecorder->recordTaken($property->fresh(), [
            'how_got_taken' => $validated['how_got_taken'] ?? null,
            'price_taken_at' => $validated['price_taken_at'] ?? null,
        ]);

        return back()->success('Property marked as taken');
    }

    public function repost(Request $request, Property $property): RedirectResponse
    {
        if ($property->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($property->taken) {
            $property->forceFill([
                'taken' => false,
                'taken_at' => null,
                'taken_warning_sent_at' => null,
                'created_at' => now(),
            ])->save();
        }

        return back()->success('Property reposted successfully');
    }

    public function destroyMyProperty(Request $request, Property $property): RedirectResponse
    {
        if ($property->user_id !== $request->user()->id) {
            abort(403);
        }

        $property->delete();

        return redirect()->route('my-properties.index')->success(__('message.propertyDeletedSuccessfully'));
    }

    public function show(Request $request, Property $property)
    {
        $property->loadMissing(['media']);

        $isMapView = $request->query('view') === 'map';

        return Inertia::modal('properties/Show', [
            'property' => PropertyData::fromModel($property)
                ->except('user', 'user_id', 'taken', 'lon', 'lat')
                ->toArray(),
        ])->baseRoute('properties.index', $isMapView ? ['view' => 'map'] : []);
    }

    public function images(Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        return response()->json([
            'images' => $this->propertyImagesPayload($property),
        ]);
    }

    public function storeImage(Request $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);

        $validated = Validator::make($request->all(), [
            'image' => ['required', 'image', 'max:10240'],
        ])->validate();

        $image = $validated['image'];

        $currentCount = (int) ($property->getFirstMedia('main_image') ? 1 : 0)
            + $property->getMedia('images')->count();

        if ($currentCount >= 6) {
            throw ValidationException::withMessages([
                'image' => 'You can upload up to 6 images.',
            ]);
        }

        if ($property->getFirstMedia('main_image')) {
            $property->addMedia($image)->toMediaCollection('images');
        } else {
            $property->addMedia($image)->toMediaCollection('main_image');
        }

        return response()->json([
            'images' => $this->propertyImagesPayload($property),
        ]);
    }

    public function destroyImage(Property $property, Media $media): JsonResponse
    {
        $this->authorize('update', $property);

        $belongsToProperty = $media->model_type === $property->getMorphClass()
            && (string) $media->model_id === (string) $property->getKey()
            && in_array($media->collection_name, ['main_image', 'images'], true);

        if (! $belongsToProperty) {
            abort(404);
        }

        $wasMainImage = $media->collection_name === 'main_image';

        $media->delete();

        if ($wasMainImage) {
            $nextMain = $property->media()
                ->where('collection_name', 'images')
                ->orderBy('order_column')
                ->orderBy('id')
                ->first();

            if ($nextMain) {
                $nextMain->move($property, 'main_image');
            }
        }

        return response()->json([
            'images' => $this->propertyImagesPayload($property),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Property $property)
    {
        $this->authorize('update', $property);

        $resolvedStreetId = $this->resolveStreetIdForUpdate($request, $property);

        if ($resolvedStreetId !== null) {
            $request->merge([
                'street' => $resolvedStreetId,
            ]);
        }

        if (is_numeric($request->input('street'))) {
            $data = PropertyFormData::validateAndCreate($request);

            Validator::make([
                'neighbourhoods' => $data->neighbourhoods,
                'street' => $data->street,
                'floor' => $data->floor,
            ], [
                'neighbourhoods' => ['required', 'array', 'min:1', 'max:3'],
                'neighbourhoods.*' => ['required', Rule::enum(Neighbourhood::class), 'distinct'],
                'street' => ['required', 'integer', 'exists:streets,id'],
                'floor' => ['required', 'numeric', 'decimal:0,1'],
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

            $coordinates = $this->propertyGeocoder->geocode(
                $streetInHebrew,
                $data->building_number !== null ? (string) $data->building_number : null,
            );

            DB::transaction(function () use ($coordinates, $data, $property, $request, $streetInHebrew): void {
                $additionalInfo = array_filter([
                    'en' => $data->additional_info_en,
                    'he' => $data->additional_info_he,
                ], fn (?string $value): bool => is_string($value) && $value !== '');

                $property->update([
                    'contact_name' => $data->contact_name,
                    'contact_phone' => $data->contact_phone,
                    'neighbourhoods' => array_values(array_unique($data->neighbourhoods)),
                    'building_number' => $data->building_number,
                    'street' => $streetInHebrew,
                    'lat' => $coordinates['lat'] ?? null,
                    'lon' => $coordinates['lon'] ?? null,
                    'floor' => $data->floor,
                    'type' => $data->type,
                    'available_from' => $data->available_from,
                    'available_to' => $data->available_to,
                    'bedrooms' => $data->bedrooms,
                    'square_meter' => $data->square_meter,
                    'furnished' => $data->furnished,
                    'bathrooms' => $data->bathrooms,
                    'access' => $data->access,
                    'kitchen_dining_room' => $data->kitchen_dining_room,
                    'porch_garden' => $data->porch_garden,
                    'succah_porch' => $data->succah_porch,
                    'air_conditioning' => $data->air_conditioning,
                    'apartment_condition' => $data->apartment_condition,
                    'additional_info' => $additionalInfo === [] ? null : $additionalInfo,
                    'has_dud_shemesh' => $data->has_dud_shemesh,
                    'has_machsan' => $data->has_machsan,
                    'has_parking_spot' => $data->has_parking_spot,
                ]);

                $this->syncUploadedImages(
                    property: $property,
                    userId: $request->user()->id,
                    tempUploadId: $data->temp_upload_id,
                    imageMediaIds: $data->image_media_ids,
                    mainImageMediaId: $data->main_image_media_id,
                );
            });

            return redirect()->route('properties.edit', $property)->success('Property updated successfully');
        }

        $validated = Validator::make($request->all(), [
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:255'],
            'neighbourhoods' => ['required', 'array', 'min:1', 'max:3'],
            'neighbourhoods.*' => ['required', Rule::enum(Neighbourhood::class), 'distinct'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'street' => ['required', 'string', 'max:255'],
            'building_number' => ['nullable'],
            'floor' => ['required', 'numeric', 'decimal:0,1'],
            'type' => ['required', Rule::enum(PropertyLeaseType::class)],
            'available_from' => ['required', 'date'],
            'available_to' => ['nullable', 'date', 'after_or_equal:available_from'],
            'bedrooms' => ['required', 'numeric', 'min:0', 'max:10'],
            'square_meter' => ['nullable', 'numeric', 'min:0'],
            'furnished' => ['required', Rule::enum(PropertyFurnished::class)],
            'bathrooms' => ['nullable', 'numeric', 'min:0'],
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
        ])->validate();

        if ($validated['type'] === PropertyLeaseType::LongTerm->value) {
            $validated['available_to'] = null;
        }

        $coordinates = $this->propertyGeocoder->geocode(
            $validated['street'],
            isset($validated['building_number']) ? (string) $validated['building_number'] : null,
        );

        DB::transaction(function () use ($coordinates, $property, $validated): void {
            $additionalInfo = array_filter([
                'en' => $validated['additional_info'] ?? null,
                'he' => $validated['additional_info'] ?? null,
            ], fn (?string $value): bool => is_string($value) && $value !== '');

            $property->update([
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_phone' => $validated['contact_phone'],
                'neighbourhoods' => array_values(array_unique($validated['neighbourhoods'])),
                'price' => $validated['price'] ?? null,
                'street' => $validated['street'],
                'building_number' => $validated['building_number'] ?? null,
                'lat' => $coordinates['lat'] ?? null,
                'lon' => $coordinates['lon'] ?? null,
                'floor' => (float) $validated['floor'],
                'type' => $validated['type'],
                'available_from' => $validated['available_from'],
                'available_to' => $validated['available_to'],
                'bedrooms' => (float) $validated['bedrooms'],
                'square_meter' => $validated['square_meter'] ?? null,
                'furnished' => $validated['furnished'],
                'bathrooms' => $validated['bathrooms'] ?? null,
                'access' => $validated['access'] ?? null,
                'kitchen_dining_room' => $validated['kitchen_dining_room'] ?? null,
                'porch_garden' => $validated['porch_garden'] ?? null,
                'succah_porch' => (bool) ($validated['succah_porch'] ?? false),
                'air_conditioning' => $validated['air_conditioning'] ?? null,
                'apartment_condition' => $validated['apartment_condition'] ?? null,
                'additional_info' => $additionalInfo === [] ? null : $additionalInfo,
                'has_dud_shemesh' => (bool) ($validated['has_dud_shemesh'] ?? false),
                'has_machsan' => (bool) ($validated['has_machsan'] ?? false),
                'has_parking_spot' => (bool) ($validated['has_parking_spot'] ?? false),
            ]);
        });

        return redirect()->route('properties.edit', $property)->success('Property updated successfully');
    }

    protected function resolveStreetIdForUpdate(Request $request, Property $property): ?int
    {
        $streetInput = $request->input('street');

        if (is_numeric($streetInput)) {
            return (int) $streetInput;
        }

        $streetName = null;

        if (is_string($streetInput) && $streetInput !== '') {
            $streetName = $streetInput;
        } elseif ($property->street !== '') {
            $streetName = $property->street;
        }

        if (! is_string($streetName) || $streetName === '') {
            return null;
        }

        $requestedNeighbourhoods = $request->input('neighbourhoods');
        $neighbourhoods = is_array($requestedNeighbourhoods)
            ? array_values(array_filter($requestedNeighbourhoods, static fn (mixed $value): bool => is_string($value) && $value !== ''))
            : [];

        if ($neighbourhoods === []) {
            $neighbourhoods = is_array($property->neighbourhoods)
                ? array_values(array_filter($property->neighbourhoods, static fn (mixed $value): bool => is_string($value) && $value !== ''))
                : [];
        }

        return Street::query()
            ->when($neighbourhoods !== [], static fn ($query) => $query->whereIn('neighbourhood', $neighbourhoods))
            ->where(function ($query) use ($streetName): void {
                $query
                    ->where('name->he', $streetName)
                    ->orWhere('name->en', $streetName);
            })
            ->value('id');
    }

    /**
     * @param  array<int, mixed>|null  $imageMediaIds
     */
    protected function syncUploadedImages(
        Property $property,
        int $userId,
        ?int $tempUploadId,
        ?array $imageMediaIds,
        ?int $mainImageMediaId,
    ): void {
        $mediaIds = collect($imageMediaIds ?? [])
            ->map(static fn (mixed $mediaId): int => (int) $mediaId)
            ->filter(static fn (int $mediaId): bool => $mediaId > 0)
            ->unique()
            ->values()
            ->all();

        if (! $tempUploadId || $mediaIds === []) {
            return;
        }

        $tempUpload = TempUpload::query()
            ->whereKey($tempUploadId)
            ->where('user_id', $userId)
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

        $mainId = $mainImageMediaId ? (int) $mainImageMediaId : $mediaIds[0];

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

        $property->clearMediaCollection('main_image');
        $property->clearMediaCollection('images');

        $mainMedia->move($property, 'main_image');

        $order = 1;
        foreach ($mediaIds as $mediaId) {
            if ($mediaId === $mainId) {
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
     * @return array<int, array{id: int, name: string, url: string, is_main: bool}>
     */
    protected function propertyImagesPayload(Property $property): array
    {
        $property->load('media');

        $payload = [];

        $mainImage = $property->getFirstMedia('main_image');

        if ($mainImage) {
            $payload[] = [
                'id' => $mainImage->id,
                'name' => $mainImage->name,
                'url' => $mainImage->getUrl(),
                'is_main' => true,
            ];
        }

        $additionalImages = $property->getMedia('images')
            ->sortBy('order_column')
            ->values();

        foreach ($additionalImages as $image) {
            $payload[] = [
                'id' => $image->id,
                'name' => $image->name,
                'url' => $image->getUrl(),
                'is_main' => false,
            ];
        }

        return $payload;
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
