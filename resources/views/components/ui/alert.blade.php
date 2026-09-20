@props([
    'type' => 'info',
    'title' => null,
    'message' => null,
    'icon' => null,
    'toast' => false,
])

@php
    $styles = [
        'success' => [
            'container' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'icon' => 'check-circle-2',
            'role' => 'status',
        ],
        'error' => [
            'container' => 'border-red-200 bg-red-50 text-red-800',
            'icon' => 'circle-alert',
            'role' => 'alert',
        ],
        'warning' => [
            'container' => 'border-amber-200 bg-amber-50 text-amber-900',
            'icon' => 'triangle-alert',
            'role' => 'alert',
        ],
        'info' => [
            'container' => 'border-brand-200 bg-brand-50/80 text-brand-950',
            'icon' => 'info',
            'role' => 'status',
        ],
    ];
    $style = $styles[$type] ?? $styles['info'];
@endphp

<div {{ $attributes->class(['flex items-start gap-3 rounded-2xl border p-4 text-sm', $style['container']]) }} role="{{ $style['role'] }}" @if($toast) data-toast-source data-toast-type="{{ $type }}" data-toast-title="{{ $title }}" @endif>
    <i data-lucide="{{ $icon ?: $style['icon'] }}" class="mt-0.5 size-5 shrink-0" aria-hidden="true"></i>

    <div class="min-w-0">
        @if($title)
            <p class="font-bold">{{ $title }}</p>
        @endif

        @if($message)
            <p class="{{ $title ? 'mt-1' : '' }} font-semibold" data-toast-source-message>{{ $message }}</p>
        @endif

        @if(! $slot->isEmpty())
            <div class="{{ $title || $message ? 'mt-1' : '' }}">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
