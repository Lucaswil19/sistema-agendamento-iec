<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            ['lider', 'secretaria', 'voluntario'],
            true
        );
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('message'))) {
            $this->merge([
                'message' => trim($this->input('message')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'message' => ['bail', 'required', 'string', 'min:3', 'max:800'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Digite uma pergunta antes de enviar.',
            'message.string' => 'A pergunta deve ser um texto.',
            'message.min' => 'A pergunta deve ter pelo menos 3 caracteres.',
            'message.max' => 'A pergunta pode ter no máximo 800 caracteres.',
        ];
    }
}
