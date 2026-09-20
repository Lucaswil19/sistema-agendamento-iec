@props([
    'id',
    'label',
    'icon',
    'active' => false,
    'badge' => null,
])

<li>
    <button
        type="button"
        class="{{ $active ? 'bg-brand-50 text-brand-800 ring-1 ring-brand-100' : 'text-stone-600 hover:bg-stone-50 hover:text-stone-900' }} group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition focus:outline-none focus:ring-4 focus:ring-brand-400/20"
        data-sidebar-group-toggle
        aria-controls="{{ $id }}"
        aria-expanded="{{ $active ? 'true' : 'false' }}"
        title="{{ $label }}"
    >
        <i data-lucide="{{ $icon }}" class="size-5 shrink-0 {{ $active ? 'text-brand-700' : 'text-stone-400 group-hover:text-brand-600' }}" aria-hidden="true"></i>
        <span class="flex-1 text-left" data-sidebar-label>{{ $label }}</span>
        @if(filled($badge))
            <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-brand-700 px-1.5 py-0.5 text-[0.65rem] font-extrabold text-white" data-sidebar-label>
                {{ is_numeric($badge) && $badge > 99 ? '99+' : $badge }}
            </span>
        @endif
        <span data-sidebar-label aria-hidden="true">
            <i data-lucide="chevron-down" class="size-4 transition-transform {{ $active ? 'rotate-180' : '' }}" data-sidebar-group-chevron></i>
        </span>
    </button>

    <ul id="{{ $id }}" class="{{ $active ? '' : 'hidden' }} ml-5 mt-1.5 space-y-1 border-l border-brand-100 pl-3" data-sidebar-group-menu>
        {{ $slot }}
    </ul>
</li>
