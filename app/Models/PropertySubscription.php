<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertySubscription extends Model
{
    protected $fillable = [
        'email',
        'user_id',
        'filters',
        'token',
        'subscribed_at',
        'expires_at',
        'unsubscribed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'subscribed_at' => 'datetime',
            'expires_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('unsubscribed_at')
            ->where('expires_at', '>', now());
    }

    public function isActive(): bool
    {
        return $this->unsubscribed_at === null && $this->expires_at->isFuture();
    }
}
