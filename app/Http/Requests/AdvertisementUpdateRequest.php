<?php

namespace App\Http\Requests;

use App\Models\Advertisement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdvertisementUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Advertisement $advertisement */
        $advertisement = $this->route('advertisement');

        return [
            'position' => [
                'required',
                'integer',
                'min:'.Advertisement::MIN_POSITION,
                'max:'.Advertisement::MAX_POSITION,
                Rule::unique('advertisements', 'position')->ignore($advertisement),
            ],
            'url' => ['required', 'url', 'max:2048'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
