<?php

namespace App\Models;

use App\Enums\PropertyAccess;
use App\Enums\PropertyAirConditioning;
use App\Enums\PropertyApartmentCondition;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyKitchenDiningRoom;
use App\Enums\PropertyLeaseType;
use App\Enums\PropertyPorchGarden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Property extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\PropertyFactory> */
    use HasFactory;

    use HasTranslations;
    use InteractsWithMedia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'neighbourhoods',
        'price',
        'street',
        'building_number',
        'floor',
        'type',
        'available_from',
        'available_to',
        'bedrooms',
        'square_meter',
        'views',
        'furnished',
        'taken',
        'bathrooms',
        'access',
        'kitchen_dining_room',
        'porch_garden',
        'succah_porch',
        'air_conditioning',
        'apartment_condition',
        'additional_info',
        'has_dud_shemesh',
        'has_machsan',
        'has_parking_spot',
    ];

    public array $translatable = ['additional_info'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected $casts = [
        'neighbourhoods' => 'array',
        'price' => 'decimal:2',
        'available_from' => 'date',
        'available_to' => 'date',
        'floor' => 'float',
        'bedrooms' => 'float',
        'views' => 'integer',
        'taken' => 'boolean',
        'succah_porch' => 'boolean',
        'has_dud_shemesh' => 'boolean',
        'has_machsan' => 'boolean',
        'has_parking_spot' => 'boolean',
        'type' => PropertyLeaseType::class,
        'furnished' => PropertyFurnished::class,
        'access' => PropertyAccess::class,
        'kitchen_dining_room' => PropertyKitchenDiningRoom::class,
        'porch_garden' => PropertyPorchGarden::class,
        'air_conditioning' => PropertyAirConditioning::class,
        'apartment_condition' => PropertyApartmentCondition::class,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_image')->singleFile();
        $this->addMediaCollection('images');
    }
}
