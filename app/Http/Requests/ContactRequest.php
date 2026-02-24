<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'is_about_dira' => ['required', 'boolean'],
            'property_id' => ['nullable', 'required_if:is_about_dira,true', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
