@extends('layout.dashboard')

@section('title', 'Próximas Ações')

@section('content')
    <x-ui.page-header
        title="Próximas ações sociais"
        :description="auth()->user()->role === 'voluntario' ? 'Consulte as próximas ações atribuídas a você.' : 'Acompanhe as ações programadas para hoje e para as próximas datas.'"
        :breadcrumbs="[['label' => 'Agenda'], ['label' => 'Próximas ações']]"
    >
        <x-slot:actions>
            <a href="{{ route('agendamento.historico') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-4 text-sm font-bold text-stone-700 shadow-sm transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/20">
                <i data-lucide="history" class="size-4" aria-hidden="true"></i>
                Histórico e pendências
            </a>

            @if(auth()->user()->role === 'lider')
                <a href="{{ route('agendamento.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 px-4 text-sm font-bold text-white shadow-[0_10px_24px_-12px_rgba(240,64,0,0.9)] transition hover:from-brand-800 hover:via-brand-700 hover:to-brand-600 focus:outline-none focus:ring-4 focus:ring-brand-400/30">
                    <i data-lucide="plus" class="size-4" aria-hidden="true"></i>
                    Novo agendamento
                </a>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-6 space-y-3">
        <x-ui.form-errors title="Verifique os filtros informados:" />

        @if($totalPendenciasVencidas > 0)
            <div class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between" role="alert">
                <div class="flex items-start gap-3">
                    <i data-lucide="triangle-alert" class="mt-0.5 size-5 shrink-0 text-amber-600" aria-hidden="true"></i>
                    <p>
                        Existem <strong>{{ $totalPendenciasVencidas }}</strong>
                        {{ $totalPendenciasVencidas === 1 ? 'ação vencida pendente' : 'ações vencidas pendentes' }} de atualização.
                    </p>
                </div>
                <a href="{{ route('agendamento.historico') }}#pendencias-vencidas" class="ml-8 inline-flex shrink-0 items-center gap-1 font-bold text-amber-800 underline decoration-amber-400 underline-offset-4 hover:text-amber-950 sm:ml-0">
                    Consultar pendências
                    <i data-lucide="chevron-right" class="size-4" aria-hidden="true"></i>
                </a>
            </div>
        @endif
    </div>

    <form method="GET" action="{{ route('agendamento.index') }}" class="mb-6 rounded-2xl border border-stone-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                    <i data-lucide="sliders-horizontal" class="size-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 class="font-extrabold text-stone-800">Filtros</h2>
                    <p class="text-xs text-stone-500">Refine os agendamentos exibidos</p>
                </div>
            </div>

            @if(request()->filled('search') || request()->filled('data_inicio') || request()->filled('data_fim') || request()->filled('status') || request()->filled('prioridade'))
                <a href="{{ route('agendamento.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-stone-500 transition hover:text-brand-800">
                    <i data-lucide="rotate-ccw" class="size-3.5" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Limpar filtros</span>
                </a>
            @endif
        </div>

        <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-12">
            <div class="form-floating form-floating-icon-tight sm:col-span-2 xl:col-span-4">
                <label for="search" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Buscar beneficiário</label>
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                    <input type="text" name="search" id="search" class="form-floating-control h-11 w-full rounded-xl border border-stone-300 bg-white pl-10 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20" value="{{ request('search') }}" maxlength="100" placeholder="Nome ou CPF">
                </div>
            </div>

            <div class="form-floating form-floating-icon-tight xl:col-span-2">
                <label for="data_inicio" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Data inicial</label>
                <div class="relative">
                    <i data-lucide="calendar-days" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                    <input type="text" name="data_inicio" id="data_inicio" class="form-floating-control h-11 w-full rounded-xl border border-stone-300 bg-white pl-10 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" data-mascara="data" value="{{ request('data_inicio') }}">
                </div>
            </div>

            <div class="form-floating form-floating-icon-tight xl:col-span-2">
                <label for="data_fim" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Data final</label>
                <div class="relative">
                    <i data-lucide="calendar-days" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                    <input type="text" name="data_fim" id="data_fim" class="form-floating-control h-11 w-full rounded-xl border border-stone-300 bg-white pl-10 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" data-mascara="data" value="{{ request('data_fim') }}">
                </div>
            </div>

            <div class="xl:col-span-2">
                <x-form.custom-select name="status" label="Status" :options="['' => 'Todos os abertos', 'agendado' => 'Agendado', 'reagendado' => 'Reagendado']" :selected="request('status', '')" />
            </div>

            <div class="xl:col-span-2">
                <x-form.custom-select name="prioridade" label="Prioridade" :options="['' => 'Todas', 'baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta', 'urgente' => 'Urgente']" :selected="request('prioridade', '')" />
            </div>
        </div>

        <div class="flex justify-end border-t border-stone-100 bg-stone-50/70 px-5 py-3.5 sm:px-6">
            <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-stone-900 px-5 text-sm font-bold text-white transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/25">
                <i data-lucide="sliders-horizontal" class="size-4" aria-hidden="true"></i>
                Aplicar filtros
            </button>
        </div>
    </form>

    <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="agenda-table-title">
        <div class="flex flex-col gap-2 border-b border-stone-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 id="agenda-table-title" class="text-lg font-extrabold text-stone-900">Ações de hoje e futuras</h2>
                <p class="mt-0.5 text-sm text-stone-500">{{ $agendamentos->total() }} {{ $agendamentos->total() === 1 ? 'agendamento encontrado' : 'agendamentos encontrados' }}</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-800 ring-1 ring-brand-100">
                <i data-lucide="calendar-days" class="size-3.5" aria-hidden="true"></i>
                Agenda atual
            </span>
        </div>

        <div class="p-2 sm:p-4">
            @include('Agendamentos.partials.tabela-tailwind', [
                'listaAgendamentos' => $agendamentos,
                'mensagemVazia' => auth()->user()->role === 'voluntario'
                    ? 'Nenhuma próxima ação foi atribuída a você.'
                    : 'Nenhuma próxima ação foi encontrada.',
            ])
        </div>
    </section>
@endsection
