<?php

namespace App\Http\Requests;

use App\Support\Cep;
use App\Support\Cpf;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeneficiarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $dados = [];

        if (is_string($this->input('cpf'))) {
            $dados['cpf'] = Cpf::normalize($this->input('cpf'));
        }

        if (is_string($this->input('cep')) || is_numeric($this->input('cep'))) {
            $cep = $this->input('cep');
            $cepNormalizado = Cep::normalize($cep);
            $dados['cep'] = $cepNormalizado === '' && trim((string) $cep) !== ''
                ? $cep
                : $cepNormalizado;
        }

        if (is_string($this->input('email'))) {
            $dados['email'] = mb_strtolower(trim($this->input('email')));
        }

        if ($dados !== []) {
            $this->merge($dados);
        }
    }

    public function rules(): array
    {
        $beneficiario = $this->route('beneficiario');

        return [
            'nome_beneficiario' => ['required', 'string', 'max:255'],
            'cpf' => [
                'bail',
                'required',
                'string',
                'digits:11',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! Cpf::isValid($value)) {
                        $fail('Informe um CPF válido.');
                    }
                },
                Rule::unique('beneficiarios', 'cpf')->ignore($beneficiario?->getKey()),
            ],
            'telefone' => ['required', 'string', 'regex:/^\+55 \(\d{2}\) \d{5}-\d{4}$/'],
            'endereco' => ['required', 'string', 'max:255'],
            'cep' => ['bail', 'required', 'string', 'digits:8'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('beneficiarios', 'email')->ignore($beneficiario?->getKey()),
            ],
            'data_nascimento' => [
                'required',
                'date_format:d/m/Y',
                function (string $attribute, mixed $value, Closure $fail): void {
                    try {
                        if (Carbon::createFromFormat('d/m/Y', (string) $value)->startOfDay()->isAfter(today())) {
                            $fail('A data de nascimento não pode ser futura.');
                        }
                    } catch (\Throwable) {
                        // A regra date_format já produz a mensagem adequada.
                    }
                },
            ],
            'possui_problema_saude' => ['required', 'boolean'],
            'descricao_problema_saude' => [
                Rule::excludeIf(fn (): bool => ! $this->boolean('possui_problema_saude')),
                Rule::requiredIf(fn (): bool => $this->boolean('possui_problema_saude')),
                'nullable',
                'string',
                'max:2000',
            ],
            'possui_deficiencia' => ['required', 'boolean'],
            'descricao_deficiencia' => [
                Rule::excludeIf(fn (): bool => ! $this->boolean('possui_deficiencia')),
                Rule::requiredIf(fn (): bool => $this->boolean('possui_deficiencia')),
                'nullable',
                'string',
                'max:2000',
            ],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome_beneficiario.required' => 'Informe o nome do beneficiário.',
            'cpf.required' => 'Informe o CPF do beneficiário.',
            'cpf.unique' => 'O CPF informado já está cadastrado.',
            'cpf.digits' => 'Informe um CPF com 11 dígitos.',
            'telefone.required' => 'Informe o telefone do beneficiário.',
            'telefone.regex' => 'Informe o telefone no formato +55 (DD) xxxxx-xxxx.',
            'endereco.required' => 'Informe o endereço do beneficiário.',
            'cep.required' => 'Informe o CEP do beneficiário.',
            'cep.string' => 'Informe um CEP válido com oito dígitos.',
            'cep.digits' => 'Informe um CEP válido com oito dígitos.',
            'email.required' => 'Informe o e-mail do beneficiário.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'O e-mail informado já está cadastrado.',
            'data_nascimento.required' => 'Informe a data de nascimento.',
            'data_nascimento.date_format' => 'Informe uma data válida no formato dd/mm/aaaa.',
            'possui_problema_saude.required' => 'Informe se o beneficiário possui problema de saúde.',
            'descricao_problema_saude.required' => 'Descreva o problema de saúde informado.',
            'descricao_problema_saude.max' => 'A descrição do problema de saúde pode ter no máximo 2.000 caracteres.',
            'possui_deficiencia.required' => 'Informe se o beneficiário possui deficiência.',
            'descricao_deficiencia.required' => 'Descreva a deficiência informada.',
            'descricao_deficiencia.max' => 'A descrição da deficiência pode ter no máximo 2.000 caracteres.',
            'observacoes.max' => 'As observações podem ter no máximo 5.000 caracteres.',
        ];
    }
}
