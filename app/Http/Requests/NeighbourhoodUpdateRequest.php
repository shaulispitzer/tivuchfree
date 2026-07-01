<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NeighbourhoodUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.he' => ['required', 'string', 'max:255'],
        ];
    }
}
