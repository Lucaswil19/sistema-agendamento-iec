@extends('layout.dashboard')

@section('title', 'Editar Usuário')

@section('content')
    @php
        $perfilAtual = (string) old('role', $user->role);
        $rotulosPerfil = [
            'lider' => 'Líder',
            'secretaria' => 'Secretária',
            'voluntario' => 'Voluntário',
        ];
    @endphp

    <div class="mx-auto max-w-4xl">
        <x-ui.page-header title="Editar usuário" description="Atualize os dados de acesso, o perfil e, se necessário, a senha deste usuário." :breadcrumbs="[['label' => 'Gestão'], ['label' => 'Usuários', 'url' => route('usuarios.index')], ['label' => $user->name, 'truncate' => true], ['label' => 'Editar']]" :back-url="route('usuarios.index')" back-label="Voltar aos usuários">
            <x-slot:badge>
                <x-ui.badge type="situation" :value="$user->is_active" dot />
            </x-slot:badge>
        </x-ui.page-header>

        <x-ui.form-errors class="mb-6" />

        <form method="POST" action="{{ route('usuarios.update', $user) }}" class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-3 border-b border-brand-100 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:px-7">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm">
                    <i data-lucide="pencil" class="size-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 class="text-lg font-extrabold text-stone-900">Dados do usuário</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Os campos marcados com * são obrigatórios.</p>
                </div>
            </div>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="identificacao-usuario-title">
                <x-form.section-heading id="identificacao-usuario-title" icon="user-round" title="Identificação" description="Dados usados para reconhecer e autenticar o usuário." />

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="name" label="Nome completo" icon="user-round" :value="$user->name" maxlength="255" autocomplete="name" placeholder="Nome e sobrenome" required autofocus />
                    <x-form.input name="email" label="E-mail" type="email" icon="mail" :value="$user->email" maxlength="255" autocomplete="email" placeholder="usuario@exemplo.com" required />
                </div>
            </section>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="perfil-usuario-title">
                <x-form.section-heading id="perfil-usuario-title" icon="user-cog" title="Perfil e permissões" description="Escolha o nível de acesso adequado às responsabilidades do usuário." />

                <div class="grid gap-5 lg:grid-cols-2">
                    <x-form.custom-select name="role" label="Perfil" :options="['' => 'Selecione um perfil'] + $rotulosPerfil" :selected="$perfilAtual" required required-message="Selecione o perfil do usuário." />

                    <div class="rounded-xl border border-brand-100 bg-brand-50/70 p-4">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 shadow-sm ring-1 ring-brand-100"><i data-lucide="info" class="size-4.5" aria-hidden="true"></i></span>
                            <div class="text-xs leading-5 text-brand-950/80">
                                <p class="text-sm font-extrabold text-brand-950">Resumo dos perfis</p>
                                <p class="mt-1"><strong>Líder:</strong> gestão completa. <strong>Secretária:</strong> agenda e consultas. <strong>Voluntário:</strong> atendimentos atribuídos.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="seguranca-usuario-title">
                <x-form.section-heading id="seguranca-usuario-title" icon="lock-keyhole" title="Alteração de senha" description="Preencha estes campos somente quando desejar definir uma nova senha." />

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-form.password name="current_password" label="Sua senha atual" maxlength="255" autocomplete="current-password" placeholder="Confirme sua identidade" help="Obrigatória somente para confirmar uma alteração de senha." input-container-class="md:max-w-[calc(50%-0.625rem)]" toggle-label="Mostrar senha atual" />
                    </div>

                    <x-form.password name="password" label="Nova senha" minlength="12" maxlength="255" autocomplete="new-password" placeholder="Deixe em branco para manter" described-by="password-help" toggle-label="Mostrar nova senha" />

                    <x-form.password name="password_confirmation" label="Confirmar nova senha" minlength="12" maxlength="255" autocomplete="new-password" placeholder="Repita a nova senha" described-by="password-help" toggle-label="Mostrar confirmação da nova senha" />

                    <div id="password-help" class="md:col-span-2 rounded-xl border border-stone-200 bg-stone-50 p-4">
                        <div class="flex items-start gap-3">
                            <i data-lucide="info" class="mt-0.5 size-4.5 shrink-0 text-brand-700" aria-hidden="true"></i>
                            <p class="text-xs leading-5 text-stone-600">Se alterar a senha, use pelo menos 12 caracteres, combinando letras maiúsculas e minúsculas, número e símbolo. Caso contrário, deixe os três campos em branco.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 py-6 sm:px-7" aria-labelledby="situacao-usuario-title">
                <div class="rounded-xl border {{ $user->is_active ? 'border-emerald-200 bg-emerald-50' : 'border-stone-200 bg-stone-50' }} p-4">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-white {{ $user->is_active ? 'text-emerald-700 ring-emerald-100' : 'text-stone-600 ring-stone-200' }} shadow-sm ring-1">
                            <i data-lucide="{{ $user->is_active ? 'check-circle-2' : 'circle-alert' }}" class="size-4.5" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h3 id="situacao-usuario-title" class="text-sm font-extrabold text-stone-800">Situação atual: {{ $user->is_active ? 'Ativo' : 'Inativo' }}</h3>
                            <p class="mt-1 text-xs leading-5 text-stone-600">A ativação e a inativação são realizadas na listagem de usuários.</p>
                        </div>
                    </div>
                </div>
            </section>

            <x-form.actions :cancel-url="route('usuarios.index')" submit-label="Salvar alterações" submit-icon="pencil" />
        </form>
    </div>
@endsection
