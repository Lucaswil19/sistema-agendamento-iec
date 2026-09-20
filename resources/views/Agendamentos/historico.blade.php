@extends('layout.dashboard')

@section('title', 'Histórico e Pendências')

@section('content')
    <x-ui.page-header
        title="Histórico e pendências"
        :description="auth()->user()->role === 'voluntario' ? 'Consulte suas ações vencidas e seus atendimentos encerrados.' : 'Consulte as ações vencidas e os atendimentos encerrados.'"
        :breadcrumbs="[['label' => 'Agenda'], ['label' => 'Histórico e pendências']]"
        :back-url="route('agendamento.index')"
        back-label="Voltar para próximas ações"
        back-icon="calendar-days"
    />

    <x-ui.form-errors title="Verifique os filtros informados:" class="mb-6" />

    <form method="GET" action="{{ route('agendamento.historico') }}" class="mb-6 rounded-2xl border border-stone-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                    <i data-lucide="sliders-horizontal" class="size-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 class="font-extrabold text-stone-800">Filtros do histórico</h2>
                    <p class="text-xs text-stone-500">Localize ações por beneficiário, período, status ou prioridade</p>
                </div>
            </div>

            @if(request()->filled('search') || request()->filled('data_inicio') || request()->filled('data_fim') || request()->filled('status') || request()->filled('prioridade'))
                <a href="{{ route('agendamento.historico') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-stone-500 transition hover:text-brand-800">
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
                <x-form.custom-select name="status" label="Status" :options="['' => 'Todos os status', 'agendado' => 'Agendado', 'reagendado' => 'Reagendado', 'completado' => 'Concluído', 'cancelado' => 'Cancelado', 'perdido' => 'Perdido']" :selected="request('status', '')" />
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

    <div class="space-y-6">
        <section id="pendencias-vencidas" class="scroll-mt-6 overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm" aria-labelledby="pendencias-title">
            <div class="flex flex-col gap-3 border-b border-amber-100 bg-amber-50/70 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-amber-200">
                        <i data-lucide="triangle-alert" class="size-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 id="pendencias-title" class="text-lg font-extrabold text-stone-900">Pendências vencidas</h2>
                        <p class="mt-0.5 max-w-3xl text-sm leading-6 text-stone-600">Ações anteriores ainda abertas que precisam ser concluídas, canceladas, marcadas como perdidas ou reagendadas.</p>
                    </div>
                </div>
                <span class="inline-flex w-fit shrink-0 items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-bold text-amber-800 ring-1 ring-amber-200">
                    <i data-lucide="clock" class="size-3.5" aria-hidden="true"></i>
                    {{ $pendencias->total() }} {{ $pendencias->total() === 1 ? 'pendência' : 'pendências' }}
                </span>
            </div>

            <div class="p-2 sm:p-4">
                @include('Agendamentos.partials.tabela-tailwind', [
                    'listaAgendamentos' => $pendencias,
                    'mensagemVazia' => auth()->user()->role === 'voluntario'
                        ? 'Você não possui ações vencidas pendentes.'
                        : 'Nenhuma pendência vencida foi encontrada.',
                ])
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="encerrados-title">
            <div class="flex flex-col gap-3 border-b border-stone-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                        <i data-lucide="history" class="size-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 id="encerrados-title" class="text-lg font-extrabold text-stone-900">Agendamentos encerrados</h2>
                        <p class="mt-0.5 text-sm leading-6 text-stone-500">Ações concluídas, canceladas ou marcadas como perdidas.</p>
                    </div>
                </div>
                <span class="inline-flex w-fit shrink-0 items-center gap-2 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-bold text-brand-800 ring-1 ring-brand-100">
                    <i data-lucide="file-text" class="size-3.5" aria-hidden="true"></i>
                    {{ $agendamentosEncerrados->total() }} {{ $agendamentosEncerrados->total() === 1 ? 'registro' : 'registros' }}
                </span>
            </div>

            <div class="p-2 sm:p-4">
                @include('Agendamentos.partials.tabela-tailwind', [
                    'listaAgendamentos' => $agendamentosEncerrados,
                    'mensagemVazia' => auth()->user()->role === 'voluntario'
                        ? 'Nenhuma ação encerrada atribuída a você foi encontrada.'
                        : 'Nenhum agendamento encerrado foi encontrado.',
                ])
            </div>
        </section>
    </div>
@endsection
