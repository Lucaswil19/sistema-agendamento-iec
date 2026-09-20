<div class="app-toast-region" data-toast-region role="region" aria-label="Notificações">
    @foreach(['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info', 'status' => 'success'] as $key => $type)
        @if(is_string(session($key)) && trim(session($key)) !== '')
            <x-ui.toast :type="$type" :message="session($key)" />
        @endif
    @endforeach
</div>
<div class="sr-only" data-toast-live="polite" role="status" aria-live="polite" aria-atomic="true"></div>
<div class="sr-only" data-toast-live="assertive" role="alert" aria-live="assertive" aria-atomic="true"></div>
<template data-toast-template>
    <x-ui.toast />
</template>
