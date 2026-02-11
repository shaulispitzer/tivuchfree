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
use App\Models\TempUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $properties = Property::query()
            ->with(['user', 'media'])
            ->latest()
            ->when($request->neighbourhood, function ($query) use ($request) {
                $query->where('neighbourhood', $request->neighbourhood);
            })
            ->get()
            ->map(fn (Property $property) => PropertyData::fromModel(
                $property,
                Auth::user()?->can('update', $property) ?? false,
            ));

        return Inertia::render('properties/Index', [
            'properties' => $properties,
            'can_create' => Auth::check(),
            'filters' => $request->all(),
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(PropertyFormData $data, Request $request)
    {

        if ($data->type === PropertyLeaseType::LongTerm) {
            $data->available_to = null;
        }
        $property = DB::transaction(function () use ($data, $request) {
            $property = Property::create([
                'user_id' => $request->user()->id,
                'street' => $data->street,
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
            'property' => PropertyData::fromModel($property, true),
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
}
