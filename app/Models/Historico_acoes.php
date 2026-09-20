<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class Historico_acoes extends Model
{
    protected $table = 'historico_acoes';

    protected $fillable = [
        'descricao',
        'tipo_atendimento',
        'data_atendimento',
        'encaminhamentos',
        'observacoes',
    ];

    protected $casts = [
        'data_atendimento' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Historico_acoes $historico): void {
            if ($historico->agendamento_id === null) {
    throw new LogicException(
        'Todo histórico de ação deve estar associado a um agendamento.'
    );
}

            $beneficiarioId = Agendamento::query()
                ->whereKey($historico->agendamento_id)
                ->value('beneficiario_id');

            if (
                $beneficiarioId !== null
                && (int) $beneficiarioId !== (int) $historico->beneficiario_id
            ) {
                throw new LogicException(
                    'O beneficiário do histórico deve ser o mesmo do agendamento.'
                );
            }
        });
    }

    public function beneficiario()
    {
        return $this->belongsTo(Beneficiario::class, 'beneficiario_id');
    }

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
