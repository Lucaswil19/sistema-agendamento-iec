<?php

namespace App\Http\Requests;

use App\Support\Cep;
use Illuminate\Foundation\Http\FormRequest;

final class ConsultarCepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $cep = $this->query('cep');

        if (is_string($cep) || is_numeric($cep)) {
            $cepNormalizado = Cep::normalize($cep);

            $this->merge([
                'cep' => $cepNormalizado === '' && trim((string) $cep) !== ''
                    ? $cep
                    : $cepNormalizado,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'cep' => ['bail', 'required', 'string', 'digits:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'cep.required' => 'Informe o CEP do beneficiário.',
            'cep.string' => 'Informe um CEP válido com oito dígitos.',
            'cep.digits' => 'Informe um CEP válido com oito dígitos.',
        ];
    }
}
