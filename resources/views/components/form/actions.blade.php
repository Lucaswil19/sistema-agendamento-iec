@props([
    'cancelUrl',
    'cancelLabel' => 'Cancelar',
    'cancelIcon' => 'x',
    'submitLabel' => 'Salvar',
    'submitIcon' => 'check-circle-2',
])

<div {{ $attributes->class(['flex flex-col-reverse gap-3 border-t border-stone-100 bg-stone-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-7']) }}>
    <a href="{{ $cancelUrl }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-5 text-sm font-bold text-stone-700 transition hover:border-stone-300 hover:bg-stone-100 focus:outline-none focus:ring-4 focus:ring-stone-300/30">
        <i data-lucide="{{ $cancelIcon }}" class="size-4" aria-hidden="true"></i>
        {{ $cancelLabel }}
    </a>
    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 px-5 text-sm font-bold text-white shadow-[0_10px_24px_-12px_rgba(240,64,0,0.9)] transition hover:from-brand-800 hover:via-brand-700 hover:to-brand-600 focus:outline-none focus:ring-4 focus:ring-brand-400/30">
        <i data-lucide="{{ $submitIcon }}" class="size-4" aria-hidden="true"></i>
        {{ $submitLabel }}
    </button>
</div>
