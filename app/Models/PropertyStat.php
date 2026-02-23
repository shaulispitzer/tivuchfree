<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyStat extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyStatFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'type',
        'neighbourhoods',
        'address',
        'how_got_taken',
        'price_advertised',
        'price_taken_at',
        'date_taken',
        'date_advertised',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'neighbourhoods' => 'array',
            'price_advertised' => 'decimal:2',
            'price_taken_at' => 'decimal:2',
            'date_taken' => 'datetime',
            'date_advertised' => 'datetime',
        ];
    }
}
