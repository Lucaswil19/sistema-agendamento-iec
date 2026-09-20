<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agendamento extends Model
{
    protected $table = 'agendamento';

    protected $fillable = [
        'beneficiario_id',
        'responsavel_id',
        'tipo_acao',
        'data_agendada',
        'hora_agendada',
        'hora_final_agendada',
        'local',
        'notas',
    ];

    protected $casts = [
        'data_agendada' => 'date',
        'lock_version' => 'integer',
        'pontuacao_prioridade' => 'integer',
    ];

    public function beneficiario()
    {
        return $this->belongsTo(Beneficiario::class, 'beneficiario_id');
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function historicoAcoes(): HasOne
{
    return $this->hasOne(Historico_acoes::class, 'agendamento_id');
}

    public function horarioAgendadoJaPassou(): bool
{
    $instanteAgendado = Carbon::createFromFormat(
        'Y-m-d H:i',
        $this->data_agendada->format('Y-m-d')
            .' '
            .substr($this->hora_agendada, 0, 5),
        config('app.timezone')
    );

    return ! $instanteAgendado->isFuture();
}
}
