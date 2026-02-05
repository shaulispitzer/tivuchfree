<?php

namespace App\Http\Requests;

use App\Enums\Neighbourhood;
use App\Models\Street;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StreetStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Street::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'neighbourhood' => ['required', Rule::enum(Neighbourhood::class)],
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.he' => ['required', 'string', 'max:255'],
        ];
    }
}
