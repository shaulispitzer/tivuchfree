<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StreetStoreRequest extends FormRequest
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
            'neighbourhood_id' => ['required', 'integer', Rule::exists('neighbourhoods', 'id')],
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.he' => ['required', 'string', 'max:255'],
        ];
    }
}
