<?php

namespace App\Models;

use App\Support\Cep;
use App\Support\Cpf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Beneficiario extends Model
{
    protected $table = 'beneficiarios';

    protected $fillable = [
        'nome_beneficiario',
        'cpf',
        'telefone',
        'endereco',
        'cep',
        'email',
        'data_nascimento',
        'possui_problema_saude',
        'descricao_problema_saude',
        'possui_deficiencia',
        'descricao_deficiencia',
        'observacoes',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'possui_problema_saude' => 'boolean',
        'possui_deficiencia' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeAtivos($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisiveisPara(Builder $query, User $user): Builder
    {
        if (in_array($user->role, ['lider', 'secretaria'], true)) {
            return $query;
        }

        if ($user->role === 'voluntario') {
            return $query->whereHas(
                'agendamentos',
                fn (Builder $query) => $query->where('responsavel_id', $user->getKey())
            );
        }

        return $query->whereRaw('1 = 0');
    }

    public function possuiAtendimentoAtribuidoA(User $user): bool
    {
        return $this->agendamentos()
            ->where('responsavel_id', $user->getKey())
            ->exists();
    }

    public function agendamentos()
    {
        return $this->hasMany(Agendamento::class, 'beneficiario_id');
    }

    public function historicoFamiliar()
    {
        return $this->hasMany(Historico_familiar::class, 'beneficiario_id');
    }

    public function historicoAcoes()
    {
        return $this->hasMany(Historico_acoes::class, 'beneficiario_id');
    }

    public function getNumeroDependentesAttribute(): int
    {
        if ($this->relationLoaded('historicoFamiliar')) {
            return $this->historicoFamiliar->count();
        }

        return $this->historicoFamiliar()->count();
    }

    public function getTotalMembrosFamiliaAttribute(): int
    {
        return $this->numero_dependentes + 1;
    }

    public function getIdadeAttribute(): ?int
    {
        if (! $this->data_nascimento) {
            return null;
        }

        return Carbon::parse($this->data_nascimento)->age;
    }

    protected function cpf(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => Cpf::format($value),
            set: fn (mixed $value): string => Cpf::normalize($value),
        );
    }

    protected function cep(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null ? null : Cep::format($value),
            set: fn (mixed $value): ?string => $value === null ? null : Cep::normalize($value),
        );
    }
}
