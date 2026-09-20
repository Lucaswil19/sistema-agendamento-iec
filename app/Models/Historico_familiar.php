<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Historico_familiar extends Model
{
    protected $table = 'historico_familiar';

    protected $fillable = [
        'nome',
        'data_nascimento',
        'parentesco',
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
    ];

    public function getIdadeAttribute(): ?int
    {
        if (! $this->data_nascimento) {
            return null;
        }

        return Carbon::parse($this->data_nascimento)->age;
    }

    public function beneficiario()
    {
        return $this->belongsTo(Beneficiario::class, 'beneficiario_id');
    }
}
