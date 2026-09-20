@props([
    'name',
    'label',
    'options',
    'id' => null,
    'selected' => '',
    'required' => false,
    'requiredMessage' => 'Selecione uma opção.',
    'help' => null,
    'maxHeight' => false,
])

@php
    $fieldId = $id ?: $name;
    $selectedValue = (string) old($name, $selected);
    $selectedLabel = $options[$selectedValue] ?? reset($options);
    $hasError = $errors->has($name);
    $labelId = $fieldId.'-label';
    $triggerId = $fieldId.'-trigger';
    $valueId = $fieldId.'-value';
    $optionsId = $fieldId.'-options';
    $errorId = $fieldId.'-error';
    $requiredErrorId = $fieldId.'-required-error';
    $helpId = $help ? $fieldId.'-help' : null;
    $describedBy = collect([
        $helpId,
        $required ? $requiredErrorId : null,
        $hasError ? $errorId : null,
    ])->filter()->implode(' ');
@endphp

<div>
    <label for="{{ $fieldId }}" id="{{ $labelId }}" class="mb-2 block text-sm font-bold text-stone-700">
        {{ $label }}
        @if($required)
            <span class="text-brand-700" aria-hidden="true">*</span>
        @endif
    </label>

    <div class="relative" data-custom-select>
        <select
            name="{{ $name }}"
            id="{{ $fieldId }}"
            data-custom-select-native
            {{ $attributes->class([
                'h-11 w-full appearance-none rounded-xl border bg-white px-3 pr-10 text-sm text-stone-900 outline-none transition',
                'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' => $hasError,
                'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' => ! $hasError,
            ]) }}
            @required($required)
            @if($hasError) aria-invalid="true" @endif
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
        >
            @foreach($options as $value => $optionLabel)
                <option value="{{ $value }}" @selected($selectedValue === (string) $value)>{{ $optionLabel }}</option>
            @endforeach
        </select>

        <button
            type="button"
            id="{{ $triggerId }}"
            data-custom-select-trigger
            class="hidden h-11 w-full min-w-0 items-center justify-between gap-3 rounded-xl border bg-white px-3 text-left text-sm text-stone-900 outline-none transition {{ $hasError ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}"
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-controls="{{ $optionsId }}"
            aria-labelledby="{{ $labelId }} {{ $valueId }}"
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if($hasError) aria-invalid="true" @endif
        >
            <span id="{{ $valueId }}" data-custom-select-value class="truncate">{{ $selectedLabel }}</span>
            <i data-lucide="chevron-down" data-custom-select-chevron class="size-4 shrink-0 text-stone-400 transition-transform" aria-hidden="true"></i>
        </button>

        <div id="{{ $optionsId }}" data-custom-select-options class="absolute left-0 right-0 top-full z-30 mt-2 hidden {{ $maxHeight ? 'max-h-64 overflow-y-auto' : 'overflow-hidden' }} rounded-xl border border-brand-100 bg-white p-1.5 shadow-[0_18px_45px_-18px_rgba(86,20,5,0.35)]" role="listbox" aria-labelledby="{{ $labelId }}">
            @foreach($options as $value => $optionLabel)
                <button type="button" data-custom-select-option data-value="{{ $value }}" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-stone-700 outline-none transition hover:bg-brand-50 hover:text-brand-800 focus:bg-brand-50 focus:text-brand-800" role="option" aria-selected="{{ $selectedValue === (string) $value ? 'true' : 'false' }}" tabindex="-1">
                    <span>{{ $optionLabel }}</span>
                    <i data-lucide="check" data-custom-select-check class="size-4 shrink-0 text-brand-600 {{ $selectedValue === (string) $value ? '' : 'hidden' }}" aria-hidden="true"></i>
                </button>
            @endforeach
        </div>
    </div>

    @if($help)
        <p id="{{ $helpId }}" class="mt-2 text-xs leading-5 text-stone-500">{{ $help }}</p>
    @endif

    @if($required)
        <p id="{{ $requiredErrorId }}" data-custom-select-required-error class="mt-2 hidden items-center gap-1.5 text-xs font-semibold text-red-600" role="alert">
            <i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>
            {{ $requiredMessage }}
        </p>
    @endif

    @if($hasError)
        <p id="{{ $errorId }}" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600">
            <i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>
            {{ $errors->first($name) }}
        </p>
    @endif
</div>
