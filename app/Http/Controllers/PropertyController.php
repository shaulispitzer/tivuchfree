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
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $properties = Property::query()
            ->with(['user', 'media'])
            ->latest()
            ->get()
            ->map(fn (Property $property) => PropertyData::fromModel(
                $property,
                Auth::user()?->can('update', $property) ?? false,
            ));

        return Inertia::render('properties/Index', [
            'properties' => $properties,
            'can_create' => Auth::check(),
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
        // dd($data, $request->all());
        // $data = $request->validated();

        if ($data->type === PropertyLeaseType::LongTerm->value) {
            $data->available_to = null;
        }
        // correct the following code to use the data object
        $property = Property::create($data->toArray() + ['user_id' => $request->user()->id]);

        // $property->addMediaFromRequest('main_image')->toMediaCollection('main_image');

        // foreach ($request->file('images', []) as $image) {
        //     $property->addMedia($image)->toMediaCollection('images');
        // }

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
