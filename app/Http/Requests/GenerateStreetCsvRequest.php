<?php

namespace App\Http\Requests;

use App\Models\Street;
use App\Services\OpenStreetMapStreetCsvGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GenerateStreetCsvRequest extends FormRequest
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
            'south' => ['required', 'numeric', 'between:-90,90'],
            'west' => ['required', 'numeric', 'between:-180,180'],
            'north' => ['required', 'numeric', 'between:-90,90', 'gt:south'],
            'east' => ['required', 'numeric', 'between:-180,180', 'gt:west'],
            'neighbourhood_id' => ['required', 'integer', 'exists:neighbourhoods,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $south = (float) $this->input('south');
            $west = (float) $this->input('west');
            $north = (float) $this->input('north');
            $east = (float) $this->input('east');
            $maxSpan = OpenStreetMapStreetCsvGenerator::maxBoundingBoxSpan();

            if (($north - $south) > $maxSpan || ($east - $west) > $maxSpan) {
                $validator->errors()->add(
                    'south',
                    'The selected area is too large. Zoom in further on OpenStreetMap before exporting coordinates.',
                );
            }
        });
    }
}
