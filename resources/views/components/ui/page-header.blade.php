@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
    'backUrl' => null,
    'backLabel' => 'Voltar',
    'backIcon' => 'chevron-left',
])

@php
    $hasActions = isset($actions) && ! $actions->isEmpty();
    $hasBadge = isset($badge) && ! $badge->isEmpty();
@endphp

<div {{ $attributes->class(['mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        @if(count($breadcrumbs))
            <nav class="mb-2 flex flex-wrap items-center gap-2 text-sm font-semibold text-stone-500" aria-label="Navegação estrutural">
                @foreach($breadcrumbs as $breadcrumb)
                    @if(! $loop->first)
                        <i data-lucide="chevron-right" class="size-4 shrink-0" aria-hidden="true"></i>
                    @endif

                    @if(! empty($breadcrumb['url']))
                        <a href="{{ $breadcrumb['url'] }}" class="min-w-0 rounded-md transition hover:text-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-400/20 {{ ! empty($breadcrumb['truncate']) ? 'max-w-48 truncate' : '' }}">
                            {{ $breadcrumb['label'] }}
                        </a>
                    @else
                        <span class="{{ $loop->last ? 'text-brand-700' : '' }} {{ ! empty($breadcrumb['truncate']) ? 'max-w-48 truncate' : '' }}">
                            {{ $breadcrumb['label'] }}
                        </span>
                    @endif
                @endforeach
            </nav>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-3xl font-extrabold tracking-tight text-stone-900 sm:text-4xl">{{ $title }}</h1>
            @if($hasBadge)
                {{ $badge }}
            @endif
        </div>

        @if($description)
            <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-500 sm:text-base">{{ $description }}</p>
        @endif
    </div>

    @if($hasActions || $backUrl)
        <div class="flex shrink-0 flex-wrap items-center gap-3 self-start sm:self-auto">
            @if($hasActions)
                {{ $actions }}
            @endif

            @if($backUrl)
                <a href="{{ $backUrl }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-4 text-sm font-bold text-stone-700 shadow-sm transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/20">
                    <i data-lucide="{{ $backIcon }}" class="size-4" aria-hidden="true"></i>
                    {{ $backLabel }}
                </a>
            @endif
        </div>
    @endif
</div>
