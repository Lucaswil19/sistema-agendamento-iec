@props([
    'id' => 'idade',
    'value' => null,
    'help' => 'Atualizada automaticamente ao alterar a data.',
    'constrained' => false,
])

@php
    $helpId = $id.'-help';
@endphp

<div>
    <label for="{{ $id }}" class="mb-2 block text-sm font-bold text-stone-700">Idade calculada</label>
    <div class="relative {{ $constrained ? 'max-w-xs' : '' }}">
        <i data-lucide="cake" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
        <input type="number" id="{{ $id }}" class="h-11 w-full cursor-not-allowed rounded-xl border border-stone-200 bg-stone-100 pl-11 pr-12 text-sm font-bold text-stone-600 outline-none" min="0" max="120" value="{{ $value }}" readonly aria-describedby="{{ $helpId }}">
        <span class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-stone-400">anos</span>
    </div>
    <p id="{{ $helpId }}" class="mt-2 text-xs text-stone-500">{{ $help }}</p>
</div>
