<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RelatorioBeneficiariosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'A página informada é inválida.',
            'page.min' => 'A página informada é inválida.',
            'page.max' => 'A página informada é inválida.',
        ];
    }
}
