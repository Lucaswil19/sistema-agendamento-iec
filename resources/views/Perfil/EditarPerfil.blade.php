@extends('layout.dashboard')

@section('title', 'Meu Perfil')

@section('content')
    @php
        $usuario = auth()->user();

        $rotuloPerfil = match ($usuario->role) {
            'lider' => 'Líder',
            'secretaria' => 'Secretária',
            'voluntario' => 'Voluntário',
            default => ucfirst($usuario->role),
        };
    @endphp

    <div class="mx-auto max-w-4xl">
        <x-ui.page-header
            title="Meu perfil"
            description="Consulte seus dados e altere sua própria senha."
            :breadcrumbs="[
                ['label' => 'Meu perfil']
            ]"
            :back-url="route('agendamento.index')"
            back-label="Voltar à agenda"
        />

        <x-ui.form-errors
            title="Não foi possível alterar a senha:"
            class="mb-6"
        />

        <section
            class="mb-6 overflow-hidden rounded-2xl border
                   border-stone-200 bg-white shadow-sm"
        >
            <div
                class="border-b border-brand-100 bg-brand-50
                       px-5 py-5 sm:px-7"
            >
                <h2 class="text-lg font-extrabold text-stone-900">
                    Dados do perfil
                </h2>

                <p class="mt-1 text-sm text-stone-500">
                    Informações do usuário conectado.
                </p>
            </div>

            <dl class="grid gap-5 px-5 py-6 sm:grid-cols-2 sm:px-7">
                <div>
                    <dt
                        class="text-xs font-extrabold uppercase
                               text-stone-400"
                    >
                        Nome
                    </dt>

                    <dd class="mt-1 text-sm font-bold text-stone-800">
                        {{ $usuario->name }}
                    </dd>
                </div>

                <div>
                    <dt
                        class="text-xs font-extrabold uppercase
                               text-stone-400"
                    >
                        E-mail
                    </dt>

                    <dd
                        class="mt-1 break-all text-sm font-bold
                               text-stone-800"
                    >
                        {{ $usuario->email }}
                    </dd>
                </div>

                <div>
                    <dt
                        class="text-xs font-extrabold uppercase
                               text-stone-400"
                    >
                        Perfil de acesso
                    </dt>

                    <dd class="mt-1 text-sm font-bold text-stone-800">
                        {{ $rotuloPerfil }}
                    </dd>
                </div>
            </dl>
        </section>

        <form
            method="POST"
            action="{{ route('perfil.senha.update') }}"
            class="overflow-hidden rounded-2xl border
                   border-stone-200 bg-white shadow-sm"
        >
            @csrf
            @method('PUT')

            <div
                class="border-b border-brand-100 bg-brand-50
                       px-5 py-5 sm:px-7"
            >
                <h2 class="text-lg font-extrabold text-stone-900">
                    Alterar senha
                </h2>

                <p class="mt-1 text-sm text-stone-500">
                    Confirme sua identidade antes de definir a nova senha.
                </p>
            </div>

            <section class="px-5 py-6 sm:px-7">
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-form.password
                            name="current_password"
                            label="Senha atual"
                            maxlength="255"
                            autocomplete="current-password"
                            placeholder="Informe sua senha atual"
                            toggle-label="Mostrar senha atual"
                            required
                            autofocus
                        />
                    </div>

                    <x-form.password
                        name="password"
                        label="Nova senha"
                        minlength="12"
                        maxlength="255"
                        autocomplete="new-password"
                        placeholder="Mínimo de 12 caracteres"
                        described-by="password-help"
                        toggle-label="Mostrar nova senha"
                        required
                    />

                    <x-form.password
                        name="password_confirmation"
                        label="Confirmar nova senha"
                        minlength="12"
                        maxlength="255"
                        autocomplete="new-password"
                        placeholder="Repita a nova senha"
                        described-by="password-help"
                        toggle-label="Mostrar confirmação da nova senha"
                        required
                    />

                    <div
                        id="password-help"
                        class="md:col-span-2 rounded-xl border
                               border-brand-100 bg-brand-50/70 p-4"
                    >
                        <p
                            class="text-xs leading-5
                                   text-brand-950/80"
                        >
                            Use pelo menos 12 caracteres, com letras
                            maiúsculas e minúsculas, número e símbolo.
                            Após a troca, esta sessão continuará ativa e
                            todas as outras sessões serão encerradas.
                        </p>
                    </div>
                </div>
            </section>

            <x-form.actions
                :cancel-url="route('agendamento.index')"
                cancel-label="Cancelar"
                submit-label="Alterar senha"
                submit-icon="lock-keyhole"
            />
        </form>
    </div>
@endsection