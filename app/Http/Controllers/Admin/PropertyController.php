<?php

namespace App\Http\Controllers\Admin;

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
use App\Http\Controllers\Controller;
use App\Mail\PropertyListingStatusChange;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    /**
     * Display a listing of all properties for admin management.
     */
    public function index(): Response
    {
        $properties = Property::query()
            ->with('user:id,name,email')
            ->latest()
            ->get()
            ->map(fn (Property $property) => [
                'id' => $property->id,
                'street' => $property->street,
                'building_number' => $property->building_number,
                'floor' => $property->floor,
                'bedrooms' => $property->bedrooms,
                'type' => $property->type?->value,
                'taken' => $property->taken,
                'reported_taken_at' => $property->reported_taken_at?->toDateTimeString(),
                'user' => [
                    'name' => $property->user?->name,
                    'email' => $property->user?->email,
                ],
            ]);

        return Inertia::render('admin/Properties', [
            'properties' => $properties,
        ]);
    }

    /**
     * Remove the specified property from storage.
     */
    public function destroy(Property $property): RedirectResponse
    {
        $this->authorize('delete', $property);

        $property->loadMissing('user');
        if ($property->user?->email) {
            $address = trim($property->street.($property->building_number ? ' '.$property->building_number : ''));
            Mail::to($property->user->email)->queue(new PropertyListingStatusChange(
                $property->user->name,
                $address,
                'deleted',
                'manually',
            ));
        }

        $property->delete();

        return redirect()->route('admin.properties.index')->success('Property deleted successfully');
    }

    /**
     * Show the form for editing the specified property (admin URL).
     */
    public function edit(Property $property): Response
    {
        $property->loadMissing(['user', 'media']);

        return Inertia::render('properties/Edit', [
            'property' => PropertyData::fromModel($property),
            'options' => $this->formOptions(),
            'lifecycle' => $this->propertyLifecycleInfo($property),
            'adminEdit' => true,
        ]);
    }

    /**
     * @return array{posted_at: string, taken: bool, next_action: string, next_action_date: string, days_remaining: int}
     */
    private function propertyLifecycleInfo(Property $property): array
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

    private function formOptions(): PropertyFormOptionsData
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
