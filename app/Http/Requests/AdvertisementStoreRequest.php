<?php

namespace App\Http\Requests;

use App\Models\Advertisement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdvertisementStoreRequest extends FormRequest
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
        return [
            'position' => [
                'required',
                'integer',
                'min:'.Advertisement::MIN_POSITION,
                'max:'.Advertisement::MAX_POSITION,
                Rule::unique('advertisements', 'position'),
            ],
            'url' => ['required', 'url', 'max:2048'],
            'is_active' => ['required', 'boolean'],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (Advertisement::query()->count() >= Advertisement::MAX_COUNT) {
                $validator->errors()->add(
                    'image',
                    'You can only have up to '.Advertisement::MAX_COUNT.' advertisements.',
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
