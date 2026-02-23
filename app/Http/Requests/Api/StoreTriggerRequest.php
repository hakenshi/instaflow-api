<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTriggerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'max:50'],
            'keywords' => ['required', 'string', 'max:2000'],
            'response_text' => ['nullable', 'string', 'max:5000', 'required_without:response_media_url'],
            'response_media_url' => ['nullable', 'url', 'max:2048', 'required_without:response_text'],
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
            'name.required' => 'O nome do gatilho é obrigatório.',
            'type.required' => 'O tipo do gatilho é obrigatório.',
            'keywords.required' => 'As palavras-chave são obrigatórias.',
            'response_text.required_without' => 'Informe um texto de resposta ou uma mídia.',
            'response_media_url.required_without' => 'Informe uma mídia ou um texto de resposta.',
            'response_media_url.url' => 'A URL da mídia precisa ser válida.',
        ];
    }
}
