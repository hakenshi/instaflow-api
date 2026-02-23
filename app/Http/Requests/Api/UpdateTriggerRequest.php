<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTriggerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'type' => ['sometimes', 'required', 'string', 'max:50'],
            'keywords' => ['sometimes', 'required', 'string', 'max:2000'],
            'response_text' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'response_media_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'match_exact' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'response_media_url.url' => 'A URL da mídia precisa ser válida.',
        ];
    }
}
