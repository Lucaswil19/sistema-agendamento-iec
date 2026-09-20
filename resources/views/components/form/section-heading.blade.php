@props([
    'id',
    'icon',
    'title',
    'description',
])

<div class="mb-5 flex items-center gap-3">
    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
        <i data-lucide="{{ $icon }}" class="size-4.5" aria-hidden="true"></i>
    </span>
    <div>
        <h3 id="{{ $id }}" class="font-extrabold text-stone-800">{{ $title }}</h3>
        <p class="text-xs text-stone-500">{{ $description }}</p>
    </div>
</div>
