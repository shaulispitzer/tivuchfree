<?php

namespace App\Http\Requests;

use App\Models\PropertySubscriptionPending;
use Illuminate\Foundation\Http\FormRequest;

class VerifyPropertySubscriptionOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ];
    }

    public function getPendingSubscription(): ?PropertySubscriptionPending
    {
        return PropertySubscriptionPending::query()
            ->where('email', $this->validated('email'))
            ->where('otp_code', $this->validated('otp'))
            ->where('otp_expires_at', '>', now())
            ->first();
    }
}
