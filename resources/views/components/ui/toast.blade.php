@props(['type' => 'info', 'title' => null, 'message' => ''])

@php
    $types = [
        'success' => ['title' => 'Sucesso', 'icon' => 'check-circle-2'],
        'error' => ['title' => 'Não foi possível concluir', 'icon' => 'circle-alert'],
        'warning' => ['title' => 'Atenção', 'icon' => 'triangle-alert'],
        'info' => ['title' => 'Informação', 'icon' => 'info'],
    ];
    $type = array_key_exists($type, $types) ? $type : 'info';
    $title = $title ?: $types[$type]['title'];
@endphp

<div class="app-toast" data-toast data-toast-type="{{ $type }}" role="group" aria-label="{{ $title }}">
    <span class="app-toast-icon" data-toast-icon>
        <i data-lucide="{{ $types[$type]['icon'] }}" aria-hidden="true"></i>
    </span>
    <div class="app-toast-content">
        <p class="app-toast-title" data-toast-title>{{ $title }}</p>
        <p class="app-toast-message" data-toast-message>{{ $message }}</p>
    </div>
    <button type="button" class="app-toast-close" data-toast-dismiss aria-label="Fechar notificação" hidden>
        <i data-lucide="x" aria-hidden="true"></i>
    </button>
    <div class="app-toast-progress" data-toast-progress aria-hidden="true" hidden></div>
</div>
