@props([
    'label',
    'value',
    'icon',
    'description' => null,
    'tone' => 'brand',
    'progress' => false,
])

@php
    $iconClasses = match ($tone) {
        'sky' => 'bg-sky-50 text-sky-700',
        'violet' => 'bg-violet-50 text-violet-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'orange' => 'bg-orange-50 text-orange-700',
        'red' => 'bg-red-50 text-red-700',
        default => 'bg-brand-50 text-brand-700',
    };
@endphp

<article {{ $attributes->class(['overflow-hidden rounded-2xl border border-stone-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm font-bold text-stone-500">{{ $label }}</p>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-stone-900">{{ $value }}</p>
        </div>
        <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl {{ $iconClasses }}">
            <i data-lucide="{{ $icon }}" class="size-5" aria-hidden="true"></i>
        </span>
    </div>

    @if($progress)
        <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-stone-100" aria-hidden="true">
            <div class="h-full w-full rounded-full bg-gradient-to-r from-brand-700 to-brand-400"></div>
        </div>
    @elseif($description)
        <p class="mt-4 text-xs font-semibold text-stone-400">{{ $description }}</p>
    @endif
</article>
