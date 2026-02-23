<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkPropertyAsTakenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'how_got_taken' => ['nullable', 'string', 'in:tivuchfree,other_non_paid,agent,other'],
            'price_taken_at' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
