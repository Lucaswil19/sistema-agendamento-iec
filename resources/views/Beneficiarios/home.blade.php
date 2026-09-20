@extends('layout.dashboard')

@section('title', 'Beneficiários')

@section('content')
    @php
        $statusAtual = (string) request('status', 'ativo');
        $rotulosStatus = [
            'ativo' => 'Ativos',
            'inativo' => 'Arquivados',
            'todos' => 'Todos',
        ];
        $rotuloStatusAtual = $rotulosStatus[$statusAtual] ?? $rotulosStatus['ativo'];
    @endphp

    <div class="mx-auto max-w-[100rem]">
        <x-ui.page-header
            title="Beneficiários"
            description="Consulte os cadastros e acompanhe as informações necessárias para cada atendimento."
            :breadcrumbs="[['label' => 'Painel'], ['label' => 'Beneficiários']]"
        >
            @if(auth()->user()->role === 'lider')
                <x-slot:actions>
                    <a href="{{ route('beneficiarios.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 px-5 text-sm font-bold text-white shadow-[0_10px_24px_-12px_rgba(240,64,0,0.9)] transition hover:from-brand-800 hover:via-brand-700 hover:to-brand-600 focus:outline-none focus:ring-4 focus:ring-brand-400/30">
                        <i data-lucide="plus" class="size-4" aria-hidden="true"></i>
                        Novo beneficiário
                    </a>
                </x-slot:actions>
            @endif
        </x-ui.page-header>

        <x-ui.form-errors :list="false" class="mb-6" />

        <section class="mb-6 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="filtros-beneficiarios-title">
            <div class="mb-5 flex items-center gap-3">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                    <i data-lucide="sliders-horizontal" class="size-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 id="filtros-beneficiarios-title" class="font-extrabold text-stone-900">Localizar beneficiários</h2>
                    <p class="mt-0.5 text-xs text-stone-500">Use a busca e a situação do cadastro para refinar os resultados.</p>
                </div>
            </div>

            <form action="{{ route('home') }}" method="GET" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_15rem_auto] lg:items-end">
                <div class="form-floating form-floating-icon">
                    <label for="search" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Buscar beneficiário</label>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                        <input type="search" name="search" id="search" class="form-floating-control h-11 w-full rounded-xl border border-stone-300 bg-white pl-11 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20" placeholder="Nome, CPF, telefone ou e-mail" value="{{ request('search') }}" maxlength="100">
                    </div>
                </div>

                <x-form.custom-select name="status" label="Situação" :options="$rotulosStatus" :selected="$statusAtual" />

                <div class="flex gap-2">
                    <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/30 lg:flex-none">
                        <i data-lucide="sliders-horizontal" class="size-4" aria-hidden="true"></i>
                        Filtrar
                    </button>
                    <a href="{{ route('home') }}" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-4 text-sm font-bold text-stone-600 transition hover:border-stone-300 hover:bg-stone-100 hover:text-stone-900 focus:outline-none focus:ring-4 focus:ring-stone-300/30 lg:flex-none">
                        <i data-lucide="x" class="size-4" aria-hidden="true"></i>
                        Limpar
                    </a>
                </div>
            </form>
        </section>

        <section aria-labelledby="resultados-beneficiarios-title">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="resultados-beneficiarios-title" class="text-lg font-extrabold text-stone-900">Resultados</h2>
                    <p class="mt-0.5 text-sm text-stone-500">
                        {{ $beneficiarios->total() }} {{ $beneficiarios->total() === 1 ? 'beneficiário encontrado' : 'beneficiários encontrados' }}
                        <span aria-hidden="true">·</span> {{ $rotuloStatusAtual }}
                    </p>
                </div>
                @if(request('search'))
                    <p class="max-w-md truncate text-sm font-semibold text-brand-800" title="{{ request('search') }}">Busca: “{{ request('search') }}”</p>
                @endif
            </div>

            @if($beneficiarios->isEmpty())
                <x-ui.empty-state title="Nenhum beneficiário encontrado" description="Revise os termos da busca ou altere a situação selecionada para encontrar outros cadastros." class="rounded-2xl border border-dashed border-brand-200 bg-white/75 shadow-sm">
                    <a href="{{ route('home') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-brand-200 bg-white px-4 text-sm font-bold text-brand-800 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-400/20">
                        <i data-lucide="rotate-ccw" class="size-4" aria-hidden="true"></i>
                        Limpar filtros
                    </a>
                </x-ui.empty-state>
            @else
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    @foreach($beneficiarios as $beneficiario)
                        <article class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_18px_45px_-28px_rgba(86,20,5,0.45)]">
                            <a href="{{ route('beneficiarios.show', $beneficiario) }}" class="flex-1 p-5 outline-none focus:ring-4 focus:ring-inset focus:ring-brand-400/20 sm:p-6" data-detail-link="beneficiario" aria-label="Ver detalhes de {{ $beneficiario->nome_beneficiario }}">
                                <div class="mb-5 flex items-start justify-between gap-3">
                                    <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-700 to-brand-400 text-lg font-extrabold text-white shadow-sm ring-4 ring-brand-50" aria-hidden="true">
                                        {{ mb_strtoupper(mb_substr($beneficiario->nome_beneficiario, 0, 1)) }}
                                    </span>
                                    <x-ui.badge type="situation" :value="$beneficiario->is_active" :label="$beneficiario->is_active ? 'Ativo' : 'Arquivado'" dot />
                                </div>

                                <h3 class="truncate text-lg font-extrabold text-stone-900 transition group-hover:text-brand-800" title="{{ $beneficiario->nome_beneficiario }}">{{ $beneficiario->nome_beneficiario }}</h3>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-stone-400">CPF {{ $beneficiario->cpf }}</p>

                                <dl class="mt-5 space-y-3 text-sm">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <dt class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-stone-100 text-stone-500"><i data-lucide="phone" class="size-4" aria-hidden="true"></i><span class="sr-only">Telefone</span></dt>
                                        <dd class="truncate font-semibold text-stone-700">{{ $beneficiario->telefone ?: 'Não informado' }}</dd>
                                    </div>
                                    <div class="flex min-w-0 items-center gap-3">
                                        <dt class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-stone-100 text-stone-500"><i data-lucide="mail" class="size-4" aria-hidden="true"></i><span class="sr-only">E-mail</span></dt>
                                        <dd class="truncate font-semibold text-stone-700" title="{{ $beneficiario->email }}">{{ $beneficiario->email ?: 'Não informado' }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-5 grid grid-cols-2 gap-2 border-t border-stone-100 pt-4">
                                    <div class="rounded-xl bg-brand-50/70 p-3">
                                        <div class="flex items-center gap-2 text-brand-700"><i data-lucide="users" class="size-4" aria-hidden="true"></i><span class="text-xs font-bold">Família</span></div>
                                        <p class="mt-1 text-sm font-extrabold text-stone-800">{{ $beneficiario->historico_familiar_count }} {{ $beneficiario->historico_familiar_count === 1 ? 'registro' : 'registros' }}</p>
                                    </div>
                                    <div class="rounded-xl bg-brand-50/70 p-3">
                                        <div class="flex items-center gap-2 text-brand-700"><i data-lucide="calendar-days" class="size-4" aria-hidden="true"></i><span class="text-xs font-bold">Agenda</span></div>
                                        <p class="mt-1 text-sm font-extrabold text-stone-800">{{ $beneficiario->agendamentos_count }} {{ $beneficiario->agendamentos_count === 1 ? 'ação' : 'ações' }}</p>
                                    </div>
                                </div>
                            </a>

                            <div class="flex flex-wrap items-center gap-2 border-t border-stone-100 bg-stone-50/70 px-5 py-3 sm:px-6">
                                <a href="{{ route('beneficiarios.show', $beneficiario) }}" class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-lg border border-stone-200 bg-white px-3 text-xs font-bold text-stone-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/20">
                                    <i data-lucide="eye" class="size-4" aria-hidden="true"></i>
                                    Ver detalhes
                                </a>

                                @if(auth()->user()->role === 'lider' && $beneficiario->is_active)
                                    <a href="{{ route('beneficiarios.edit', $beneficiario) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-brand-200 bg-brand-50 px-3 text-xs font-bold text-brand-800 transition hover:border-brand-300 hover:bg-brand-100 focus:outline-none focus:ring-4 focus:ring-brand-400/20">
                                        <i data-lucide="pencil" class="size-4" aria-hidden="true"></i>
                                        Editar
                                    </a>
                                @elseif(auth()->user()->role === 'lider')
                                    <form method="POST" action="{{ route('beneficiarios.reativar', $beneficiario) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-3 text-xs font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-300/40">
                                            <i data-lucide="rotate-ccw" class="size-4" aria-hidden="true"></i>
                                            Reativar
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($beneficiarios->hasPages())
                    <div class="mt-7 rounded-2xl border border-stone-200 bg-white px-4 py-3 shadow-sm">
                        {{ $beneficiarios->withQueryString()->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
