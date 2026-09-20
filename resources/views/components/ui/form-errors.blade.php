@props([
    'title' => 'Verifique os campos informados:',
    'list' => true,
])

@if($errors->any())
    <x-ui.alert type="error" toast :title="$list ? $title : null" class="{{ $attributes->get('class') }}">
        @if($list)
            <ul class="list-disc space-y-0.5 pl-5 text-red-700">
                @foreach($errors->all() as $error)
                    <li data-toast-source-message>{{ $error }}</li>
                @endforeach
            </ul>
        @else
            <p class="font-semibold" data-toast-source-message>{{ $errors->first() }}</p>
        @endif
    </x-ui.alert>
@endif
