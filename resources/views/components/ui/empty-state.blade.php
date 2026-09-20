@props([
    'title',
    'description' => null,
    'icon' => 'search',
])

<div {{ $attributes->class(['px-6 py-16 text-center']) }}>
    <span class="mx-auto mb-4 inline-flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
        <i data-lucide="{{ $icon }}" class="size-7" aria-hidden="true"></i>
    </span>
    <h3 class="font-extrabold text-stone-900">{{ $title }}</h3>

    @if($description)
        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-500">{{ $description }}</p>
    @endif

    @if(! $slot->isEmpty())
        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
