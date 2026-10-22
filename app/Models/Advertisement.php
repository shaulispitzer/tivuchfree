<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class Advertisement extends Model
{
    /** @use HasFactory<\Database\Factories\AdvertisementFactory> */
    use HasFactory;

    public const MAX_COUNT = 4;

    public const MIN_POSITION = 1;

    public const MAX_POSITION = 4;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'position',
        'url',
        'is_active',
        'image_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function imageUrl(): string
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($this->image_path);
    }

    /**
     * @param  Builder<Advertisement>  $query
     * @return Builder<Advertisement>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return list<array{id: int, url: string, image_url: string, position: int}>
     */
    public static function activeForList(): array
    {
        return static::query()
            ->active()
            ->orderBy('position')
            ->get()
            ->map(fn (self $ad): array => [
                'id' => $ad->id,
                'url' => $ad->url,
                'image_url' => $ad->imageUrl(),
                'position' => $ad->position,
            ])
            ->all();
    }

    public function deleteImage(): void
    {
        if ($this->image_path !== '' && Storage::disk('public')->exists($this->image_path)) {
            Storage::disk('public')->delete($this->image_path);
        }
    }
}
