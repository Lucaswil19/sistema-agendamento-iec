@props([
    'name',
    'label',
    'id' => null,
    'value' => null,
    'icon' => null,
    'required' => false,
    'help' => null,
    'rows' => 4,
    'compact' => false,
    'optional' => false,
])

@php
    $fieldId = $id ?: $name;
    $hasError = $errors->has($name);
    $errorId = $fieldId.'-error';
    $helpId = $help ? $fieldId.'-help' : null;
    $describedBy = collect([$helpId, $hasError ? $errorId : null])->filter()->implode(' ');
@endphp

<div @class(['form-floating', 'form-floating-icon' => $icon])>
    <label for="{{ $fieldId }}" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">
        {{ $label }}
        @if($required)
            <span class="text-brand-700" aria-hidden="true">*</span>
        @elseif($optional)
            <span class="font-medium text-stone-400">(opcional)</span>
        @endif
    </label>

    <div class="relative">
        @if($icon)
            <i data-lucide="{{ $icon }}" class="pointer-events-none absolute left-3.5 top-3.5 size-4.5 text-stone-400" aria-hidden="true"></i>
        @endif

        <textarea
            name="{{ $name }}"
            id="{{ $fieldId }}"
            rows="{{ $rows }}"
            {{ $attributes->merge(['placeholder' => ' '])->class([
                'form-floating-control w-full resize-y rounded-xl border bg-white py-3 text-sm leading-6 text-stone-900 outline-none transition placeholder:text-stone-400',
                $compact ? 'min-h-28' : 'min-h-32',
                $icon ? 'pl-11 pr-3' : 'px-3',
                'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' => $hasError,
                'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' => ! $hasError,
            ]) }}
            @required($required)
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if($hasError) aria-invalid="true" @endif
        >{{ old($name, $value) }}</textarea>
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
