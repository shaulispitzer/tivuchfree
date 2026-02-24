<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertySubscriptionPending extends Model
{
    protected $fillable = [
        'email',
        'filters',
        'otp_code',
        'otp_expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'otp_expires_at' => 'datetime',
        ];
    }

    public function isOtpExpired(): bool
    {
        return $this->otp_expires_at->isPast();
    }
}
