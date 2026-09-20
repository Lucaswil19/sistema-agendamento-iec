@props([
    'href',
    'label',
    'icon',
    'active' => false,
    'title' => null,
])

<li>
    <a
        href="{{ $href }}"
        {{ $attributes->class([
            'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition',
            'bg-brand-50 text-brand-800' => $active,
            'text-stone-500 hover:bg-stone-50 hover:text-stone-900' => ! $active,
        ]) }}
        data-sidebar-link
        title="{{ $title ?: $label }}"
        @if($active) aria-current="page" @endif
    >
        <i data-lucide="{{ $icon }}" class="size-4 shrink-0 {{ $active ? 'text-brand-700' : 'text-stone-400 group-hover:text-brand-600' }}" aria-hidden="true"></i>
        <span class="flex-1" data-sidebar-label>{{ $label }}</span>
    </a>
</li>
