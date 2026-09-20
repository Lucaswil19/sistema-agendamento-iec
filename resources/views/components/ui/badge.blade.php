@props([
    'type' => 'default',
    'value' => null,
    'label' => null,
    'icon' => null,
    'dot' => false,
    'size' => 'default',
    'tone' => null,
])

@php
    $normalizedValue = is_bool($value)
        ? ($value ? 'ativo' : 'inativo')
        : Illuminate\Support\Str::lower((string) $value);

    $definitions = [
        'status' => [
            'agendado' => ['Agendado', 'sky'],
            'reagendado' => ['Reagendado', 'violet'],
            'completado' => ['Concluído', 'emerald'],
            'concluido' => ['Concluído', 'emerald'],
            'cancelado' => ['Cancelado', 'red'],
            'perdido' => ['Perdido', 'stone'],
        ],
        'priority' => [
            'baixa' => ['Baixa', 'emerald'],
            'media' => ['Média', 'amber'],
            'alta' => ['Alta', 'orange'],
            'urgente' => ['Urgente', 'red'],
        ],
        'role' => [
            'lider' => ['Líder', 'brand'],
            'secretaria' => ['Secretária', 'sky'],
            'voluntario' => ['Voluntário', 'violet'],
        ],
        'situation' => [
            'ativo' => ['Ativo', 'emerald'],
            'active' => ['Ativo', 'emerald'],
            'inativo' => ['Inativo', 'stone'],
            'inactive' => ['Inativo', 'stone'],
            'arquivado' => ['Arquivado', 'stone'],
        ],
    ];

    [$defaultLabel, $defaultTone] = $definitions[$type][$normalizedValue]
        ?? [filled($value) ? Illuminate\Support\Str::ucfirst((string) $value) : 'Não informado', 'stone'];

    $badgeLabel = $label ?: $defaultLabel;
    $badgeTone = $tone ?: $defaultTone;
    $toneClasses = match ($badgeTone) {
        'brand' => 'bg-brand-50 text-brand-800 ring-brand-200',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-200',
        'red' => 'bg-red-50 text-red-700 ring-red-200',
        default => 'bg-stone-100 text-stone-600 ring-stone-200',
    };
    $dotClasses = match ($badgeTone) {
        'brand' => 'bg-brand-500',
        'sky' => 'bg-sky-500',
        'violet' => 'bg-violet-500',
        'emerald' => 'bg-emerald-500',
        'amber' => 'bg-amber-500',
        'orange' => 'bg-orange-500',
        'red' => 'bg-red-500',
        default => 'bg-stone-400',
    };
@endphp

<span {{ $attributes->class([
    'inline-flex items-center rounded-full font-bold ring-1 ring-inset',
    $size === 'large' ? 'gap-1.5 px-3 py-1.5 text-xs' : 'gap-1.5 px-2.5 py-1 text-xs',
    $toneClasses,
]) }}>
    @if($dot)
        <span class="size-1.5 rounded-full {{ $dotClasses }}" aria-hidden="true"></span>
    @endif
    @if($icon)
        <i data-lucide="{{ $icon }}" class="size-3.5" aria-hidden="true"></i>
    @endif
    {{ $badgeLabel }}
</span>
