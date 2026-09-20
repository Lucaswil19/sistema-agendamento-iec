<?php

namespace App\Services;

use App\Models\Agendamento;
use DomainException;

class MaquinaEstadosAgendamento
{
    public const AGENDADO = 'agendado';

    public const REAGENDADO = 'reagendado';

    public const COMPLETADO = 'completado';

    public const CANCELADO = 'cancelado';

    public const PERDIDO = 'perdido';

    public const STATUS_ABERTOS = [
        self::AGENDADO,
        self::REAGENDADO,
    ];

    public const STATUS_ENCERRADOS = [
    self::COMPLETADO,
    self::CANCELADO,
    self::PERDIDO,
    ];

    public const STATUS_REABRIVEIS = [
        self::CANCELADO,
        self::PERDIDO,
    ];

    private const TRANSICOES_PERMITIDAS = [
        self::AGENDADO => [
            self::REAGENDADO,
            self::COMPLETADO,
            self::CANCELADO,
            self::PERDIDO,
        ],
        self::REAGENDADO => [
            self::COMPLETADO,
            self::CANCELADO,
            self::PERDIDO,
        ],
        self::COMPLETADO => [],
        self::CANCELADO => [
            self::REAGENDADO,
        ],
        self::PERDIDO => [
            self::REAGENDADO,
        ],
    ];

    public function podeTransicionar(string $statusAtual, string $novoStatus): bool
    {
        return in_array(
            $novoStatus,
            self::TRANSICOES_PERMITIDAS[$statusAtual] ?? [],
            true
        );
    }

    public function transicionar(Agendamento $agendamento, string $novoStatus): void
    {
        if (! $this->podeTransicionar($agendamento->status, $novoStatus)) {
            throw new DomainException(sprintf(
                'A transição de "%s" para "%s" não é permitida.',
                $agendamento->status,
                $novoStatus
            ));
        }

        $agendamento->status = $novoStatus;

        if ($novoStatus !== self::CANCELADO) {
            $agendamento->motivo_cancelamento = null;
        }

        $agendamento->lock_version = (int) $agendamento->lock_version + 1;
    }

    public function transicionarCondicional(
        Agendamento $agendamento,
        string $novoStatus,
        array $atributos = []
    ): bool {
        $statusAtual = (string) $agendamento->status;
        $versaoAtual = (int) $agendamento->lock_version;

        if (! $this->podeTransicionar($statusAtual, $novoStatus)) {
            throw new DomainException(sprintf(
                'A transição de "%s" para "%s" não é permitida.',
                $statusAtual,
                $novoStatus
            ));
        }

        unset(
            $atributos['status'],
            $atributos['lock_version'],
            $atributos['updated_at']
        );

        if ($novoStatus !== self::CANCELADO) {
            $atributos['motivo_cancelamento'] = null;
        }

        $atributos['status'] = $novoStatus;
        $atributos['lock_version'] = $versaoAtual + 1;
        $atributos['updated_at'] = now();

        $atualizados = Agendamento::query()
            ->whereKey($agendamento->getKey())
            ->where('status', $statusAtual)
            ->where('lock_version', $versaoAtual)
            ->update($atributos);

        if ($atualizados !== 1) {
            return false;
        }

        $agendamento->forceFill($atributos);
        $agendamento->syncOriginal();

        return true;
    }
}
