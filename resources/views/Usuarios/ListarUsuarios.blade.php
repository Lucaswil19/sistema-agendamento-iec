@extends('layout.dashboard')

@section('title', 'Gerenciamento de Usuários')

@section('content')
    @php
        $perfilAtual = (string) request('role', '');
        $statusAtual = (string) request('status', '');

        $rotulosPerfil = [
            '' => 'Todos os perfis',
            'lider' => 'Líder',
            'secretaria' => 'Secretária',
            'voluntario' => 'Voluntário',
        ];

        $rotulosStatus = [
            '' => 'Todas as situações',
            'ativo' => 'Ativos',
            'inativo' => 'Inativos',
        ];

        $possuiFiltros = request()->filled('search') || request()->filled('role') || request()->filled('status');
    @endphp

    <div class="mx-auto max-w-[100rem]">
        <x-ui.page-header title="Gerenciamento de usuários" description="Cadastre, edite e controle os acessos da equipe com segurança." :breadcrumbs="[['label' => 'Gestão'], ['label' => 'Usuários']]">
            <x-slot:actions>
                <a href="{{ route('usuarios.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 px-5 text-sm font-bold text-white shadow-[0_10px_24px_-12px_rgba(240,64,0,0.9)] transition hover:from-brand-800 hover:via-brand-700 hover:to-brand-600 focus:outline-none focus:ring-4 focus:ring-brand-400/30">
                    <i data-lucide="plus" class="size-4" aria-hidden="true"></i>
                    Novo usuário
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.form-errors title="Não foi possível concluir a operação:" class="mb-6" />

        <section class="mb-6 rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="filtros-usuarios-title">
            <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="sliders-horizontal" class="size-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 id="filtros-usuarios-title" class="font-extrabold text-stone-900">Localizar usuários</h2>
                        <p class="mt-0.5 text-xs text-stone-500">Refine os resultados por nome, e-mail, perfil ou situação.</p>
                    </div>
                </div>

                @if($possuiFiltros)
                    <a href="{{ route('usuarios.index') }}" class="hidden items-center gap-1.5 text-xs font-bold text-stone-500 transition hover:text-brand-800 sm:inline-flex">
                        <i data-lucide="rotate-ccw" class="size-3.5" aria-hidden="true"></i>
                        Limpar filtros
                    </a>
                @endif
            </div>

            <form method="GET" action="{{ route('usuarios.index') }}" class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-12 xl:items-end">
                <div class="form-floating form-floating-icon sm:col-span-2 xl:col-span-5">
                    <label for="search" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Buscar por nome ou e-mail</label>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                        <input type="search" name="search" id="search" class="form-floating-control h-11 w-full rounded-xl border border-stone-300 bg-white pl-11 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20" value="{{ request('search') }}" maxlength="100" placeholder="Digite o nome ou e-mail">
                    </div>
                </div>

                <div class="xl:col-span-3">
                    <x-form.custom-select name="role" label="Perfil" :options="$rotulosPerfil" :selected="$perfilAtual" />
                </div>

                <div class="xl:col-span-2">
                    <x-form.custom-select name="status" label="Situação" :options="$rotulosStatus" :selected="$statusAtual" />
                </div>

                <div class="flex gap-2 sm:col-span-2 xl:col-span-2">
                    <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/30">
                        <i data-lucide="sliders-horizontal" class="size-4" aria-hidden="true"></i>
                        Filtrar
                    </button>
                    <a href="{{ route('usuarios.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-stone-200 bg-white px-3 text-stone-600 transition hover:border-stone-300 hover:bg-stone-100 hover:text-stone-900 focus:outline-none focus:ring-4 focus:ring-stone-300/30" title="Limpar filtros" aria-label="Limpar filtros">
                        <i data-lucide="x" class="size-4" aria-hidden="true"></i>
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="usuarios-table-title">
            <div class="flex flex-col gap-3 border-b border-stone-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 id="usuarios-table-title" class="text-lg font-extrabold text-stone-900">Usuários cadastrados</h2>
                    <p class="mt-0.5 text-sm text-stone-500">
                        {{ $usuarios->total() }} {{ $usuarios->total() === 1 ? 'usuário encontrado' : 'usuários encontrados' }}
                    </p>
                </div>

                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-800 ring-1 ring-brand-100">
                    <i data-lucide="users" class="size-3.5" aria-hidden="true"></i>
                    Gestão de acessos
                </span>
            </div>

            <div class="overflow-x-auto p-2 sm:p-4">
                <table class="w-full min-w-[72rem] border-separate border-spacing-0 text-left">
                    <thead>
                        <tr class="bg-stone-50 text-[0.68rem] font-extrabold uppercase tracking-[0.12em] text-stone-500">
                            <th scope="col" class="rounded-l-xl border-y border-l border-stone-200 px-5 py-3.5">Usuário</th>
                            <th scope="col" class="border-y border-stone-200 px-5 py-3.5">E-mail</th>
                            <th scope="col" class="border-y border-stone-200 px-5 py-3.5">Perfil</th>
                            <th scope="col" class="border-y border-stone-200 px-5 py-3.5">Situação</th>
                            <th scope="col" class="border-y border-stone-200 px-5 py-3.5">Vínculos</th>
                            <th scope="col" class="rounded-r-xl border-y border-r border-stone-200 px-5 py-3.5 text-right">Ações</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-stone-100">
                        @forelse($usuarios as $user)
                            @php
                                $totalVinculos = $user->agendamentos_criados_count
                                    + $user->agendamentos_responsavel_count
                                    + $user->historico_acoes_count;

                            @endphp

                            <tr class="group transition hover:bg-brand-50/55">
                                <td class="px-5 py-4">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-700 to-brand-400 text-sm font-extrabold text-white shadow-sm ring-4 ring-brand-50" aria-hidden="true">
                                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="max-w-52 truncate text-sm font-extrabold text-stone-900" title="{{ $user->name }}">{{ $user->name }}</p>
                                                @if(auth()->id() === $user->id)
                                                    <span class="inline-flex shrink-0 rounded-full bg-sky-50 px-2 py-0.5 text-[0.65rem] font-extrabold uppercase tracking-wide text-sky-700 ring-1 ring-inset ring-sky-200">Você</span>
                                                @endif
                                            </div>
                                            <p class="mt-0.5 text-xs text-stone-400">ID {{ $user->id }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-sm text-stone-600">
                                    <div class="flex max-w-64 items-center gap-2">
                                        <i data-lucide="mail" class="size-4 shrink-0 text-stone-400" aria-hidden="true"></i>
                                        <span class="truncate" title="{{ $user->email }}">{{ $user->email }}</span>
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    <x-ui.badge type="role" :value="$user->role" />
                                </td>

                                <td class="px-5 py-4">
                                    <x-ui.badge type="situation" :value="$user->is_active" dot />
                                </td>

                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-stone-600" title="Total de registros vinculados">
                                        <i data-lucide="clipboard-list" class="size-4 text-stone-400" aria-hidden="true"></i>
                                        {{ $totalVinculos }} {{ $totalVinculos === 1 ? 'registro' : 'registros' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <a href="{{ route('usuarios.edit', $user) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-brand-200 bg-brand-50 px-3 text-xs font-bold text-brand-800 transition hover:border-brand-300 hover:bg-brand-100 focus:outline-none focus:ring-4 focus:ring-brand-400/20">
                                            <i data-lucide="pencil" class="size-4" aria-hidden="true"></i>
                                            Editar
                                        </a>

                                        @if(auth()->id() !== $user->id)
                                            @if($user->is_active)
                                                <form method="POST" action="{{ route('usuarios.inativar', $user) }}" onsubmit="return confirm('Deseja realmente inativar este usuário?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-stone-200 bg-white px-3 text-xs font-bold text-stone-600 transition hover:border-stone-300 hover:bg-stone-100 hover:text-stone-900 focus:outline-none focus:ring-4 focus:ring-stone-300/30">
                                                        <i data-lucide="x" class="size-4" aria-hidden="true"></i>
                                                        Inativar
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('usuarios.reativar', $user) }}" onsubmit="return confirm('Deseja reativar este usuário?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-3 text-xs font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-300/40">
                                                        <i data-lucide="archive-restore" class="size-4" aria-hidden="true"></i>
                                                        Reativar
                                                    </button>
                                                </form>
                                            @endif

                                            @if(!$user->is_active && $totalVinculos === 0)
                                                <form method="POST" action="{{ route('usuarios.destroy', $user) }}" onsubmit="return confirm('A exclusão é permanente. Deseja continuar?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex size-9 items-center justify-center rounded-lg text-red-600 transition hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-4 focus:ring-red-300/30" title="Excluir permanentemente" aria-label="Excluir permanentemente {{ $user->name }}">
                                                        <i data-lucide="trash-2" class="size-4" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-500" title="Sua conta não pode ser inativada ou excluída nesta sessão">
                                                <i data-lucide="info" class="size-3.5" aria-hidden="true"></i>
                                                Conta atual
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-0">
                                    <x-ui.empty-state title="Nenhum usuário encontrado" description="Revise os termos informados ou limpe os filtros para consultar todos os usuários.">
                                        @if($possuiFiltros)
                                            <a href="{{ route('usuarios.index') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-brand-200 bg-white px-4 text-sm font-bold text-brand-800 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-400/20">
                                                <i data-lucide="rotate-ccw" class="size-4" aria-hidden="true"></i>
                                                Limpar filtros
                                            </a>
                                        @endif
                                    </x-ui.empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($usuarios->hasPages())
                <div class="border-t border-stone-100 px-5 py-4">
                    {{ $usuarios->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
