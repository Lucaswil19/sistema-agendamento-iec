@props([
    'name',
    'label',
    'id' => null,
    'required' => false,
    'help' => null,
    'size' => 'default',
    'toggleLabel' => null,
    'describedBy' => null,
    'inputContainerClass' => null,
])

@php
    $fieldId = $id ?: $name;
    $hasError = $errors->has($name);
    $errorId = $fieldId.'-error';
    $helpId = $help ? $fieldId.'-help' : null;
    $ariaDescribedBy = collect([$describedBy, $helpId, $hasError ? $errorId : null])->filter()->implode(' ');
    $isLarge = $size === 'large';
    $showLabel = $toggleLabel ?: 'Mostrar '.mb_strtolower($label);
    $iconClasses = $isLarge ? 'left-4 size-5' : 'left-3.5 size-4.5';
@endphp

<div @class(['form-floating form-floating-icon form-floating-password', 'form-floating-large' => $isLarge])>
    <label for="{{ $fieldId }}" class="form-floating-label mb-2 block text-sm {{ $isLarge ? 'font-semibold' : 'font-bold' }} text-stone-700">
        {{ $label }}
        @if($required)
            <span class="text-brand-700" aria-hidden="true">*</span>
        @endif
    </label>

    <div class="relative {{ $inputContainerClass }}">
        <i data-lucide="lock-keyhole" class="pointer-events-none absolute top-1/2 -translate-y-1/2 text-stone-400 {{ $iconClasses }}" aria-hidden="true"></i>
        <input
            type="password"
            name="{{ $name }}"
            id="{{ $fieldId }}"
            {{ $attributes->merge(['placeholder' => ' '])->class([
                'form-floating-control w-full rounded-xl border bg-white text-sm text-stone-900 outline-none transition placeholder:text-stone-400',
                $isLarge ? 'h-12 pl-12 pr-12' : 'h-11 pl-11 pr-11',
                'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' => $hasError,
                'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' => ! $hasError,
            ]) }}
            @required($required)
            @if($ariaDescribedBy) aria-describedby="{{ $ariaDescribedBy }}" @endif
            @if($hasError) aria-invalid="true" @endif
        >
        <button
            type="button"
            data-password-field-toggle="{{ $fieldId }}"
            class="absolute right-1.5 top-1/2 inline-flex {{ $isLarge ? 'size-9' : 'size-8' }} -translate-y-1/2 items-center justify-center rounded-lg text-stone-400 transition hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-400/20"
            aria-label="{{ $showLabel }}"
            data-password-show-label="{{ $showLabel }}"
            data-password-hide-label="Ocultar {{ mb_strtolower($label) }}"
            aria-pressed="false"
        >
            <span data-password-show-icon aria-hidden="true"><i data-lucide="eye" class="{{ $isLarge ? 'size-5' : 'size-4' }}"></i></span>
            <span data-password-hide-icon class="hidden" aria-hidden="true"><i data-lucide="eye-off" class="{{ $isLarge ? 'size-5' : 'size-4' }}"></i></span>
        </button>
    </div>

    @if($help)
        <p id="{{ $helpId }}" class="mt-2 text-xs leading-5 text-stone-500">{{ $help }}</p>
    @endif

    @if($hasError)
        <p id="{{ $errorId }}" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600">
            <i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>
            {{ $errors->first($name) }}
        </p>
    @endif
</div>
