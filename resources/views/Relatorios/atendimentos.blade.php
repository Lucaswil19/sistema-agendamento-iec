@extends('layout.dashboard')

@section('title', 'Relatório de Atendimentos')

@section('content')
    <div class="mx-auto max-w-[100rem]">
        <x-ui.page-header title="Relatório de atendimentos" description="Analise os atendimentos efetivamente realizados conforme a data registrada no relatório de cada ação." :breadcrumbs="[['label' => 'Relatórios'], ['label' => 'Atendimentos']]" :back-url="route('agendamento.index')" back-label="Voltar para a agenda" />

        <x-ui.form-errors title="Verifique os filtros informados:" class="mb-6" />

        <section class="mb-6 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="filtros-atendimentos-title">
            <div class="mb-5 flex items-center gap-3">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><i data-lucide="sliders-horizontal" class="size-5" aria-hidden="true"></i></span>
                <div>
                    <h2 id="filtros-atendimentos-title" class="font-extrabold text-stone-900">Período do relatório</h2>
                    <p class="mt-0.5 text-xs text-stone-500">Deixe as datas vazias para considerar todo o histórico registrado.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('relatorios.atendimentos') }}" class="grid gap-4 md:grid-cols-2 lg:grid-cols-[15rem_15rem_auto] lg:items-end">
                <div class="form-floating form-floating-icon">
                    <label for="data_inicio" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Data inicial</label>
                    <div class="relative">
                        <i data-lucide="calendar-days" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                        <input type="text" name="data_inicio" id="data_inicio" class="form-floating-control h-11 w-full rounded-xl border bg-white pl-11 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 {{ $errors->has('data_inicio') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" value="{{ old('data_inicio', request('data_inicio')) }}" data-mascara="data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" aria-describedby="data-inicio-help @error('data_inicio') data-inicio-error @enderror" @error('data_inicio') aria-invalid="true" @enderror>
                    </div>
                    <p id="data-inicio-help" class="mt-2 text-xs text-stone-500">Início do período realizado.</p>
                    @error('data_inicio')
                        <p id="data-inicio-error" class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-red-600"><i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-floating form-floating-icon">
                    <label for="data_fim" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Data final</label>
                    <div class="relative">
                        <i data-lucide="calendar-days" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                        <input type="text" name="data_fim" id="data_fim" class="form-floating-control h-11 w-full rounded-xl border bg-white pl-11 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 {{ $errors->has('data_fim') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" value="{{ old('data_fim', request('data_fim')) }}" data-mascara="data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" aria-describedby="data-fim-help @error('data_fim') data-fim-error @enderror" @error('data_fim') aria-invalid="true" @enderror>
                    </div>
                    <p id="data-fim-help" class="mt-2 text-xs text-stone-500">Fim do período realizado.</p>
                    @error('data_fim')
                        <p id="data-fim-error" class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-red-600"><i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-2 md:col-span-2 lg:col-span-1">
                    <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/30 lg:flex-none">
                        <i data-lucide="sliders-horizontal" class="size-4" aria-hidden="true"></i>
                        Filtrar
                    </button>
                    <a href="{{ route('relatorios.atendimentos') }}" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-4 text-sm font-bold text-stone-600 transition hover:border-stone-300 hover:bg-stone-100 hover:text-stone-900 focus:outline-none focus:ring-4 focus:ring-stone-300/30 lg:flex-none">
                        <i data-lucide="x" class="size-4" aria-hidden="true"></i>
                        Limpar
                    </a>
                </div>
            </form>
        </section>

        <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores do relatório">
            <x-ui.stat-card label="Atendimentos realizados" :value="$totalAtendimentos" icon="check-circle-2" progress />
            <x-ui.stat-card label="Beneficiários atendidos" :value="$totalBeneficiarios" icon="users" tone="sky" description="Pessoas distintas no período" />
            <x-ui.stat-card label="Atendimentos urgentes" :value="$totalUrgentes" icon="triangle-alert" tone="red" description="Prioridade urgente registrada" />
            <x-ui.stat-card label="Com encaminhamento" :value="$totalComEncaminhamento" icon="clipboard-list" tone="emerald" description="Ações com orientação posterior" />
        </section>

        <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="lista-atendimentos-title">
            <div class="flex flex-col gap-2 border-b border-stone-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><i data-lucide="bar-chart-3" class="size-5" aria-hidden="true"></i></span>
                    <div><h2 id="lista-atendimentos-title" class="text-lg font-extrabold text-stone-900">Lista de atendimentos</h2><p class="mt-0.5 text-sm text-stone-500">{{ $atendimentos->total() }} {{ $atendimentos->total() === 1 ? 'registro encontrado' : 'registros encontrados' }}</p></div>
                </div>
                @if(request('data_inicio') || request('data_fim'))
                    <span class="inline-flex self-start rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-800 ring-1 ring-brand-100 sm:self-auto">Período filtrado</span>
                @endif
            </div>

            @if($atendimentos->isEmpty())
                <x-ui.empty-state title="Nenhum atendimento encontrado" description="Não existem atendimentos realizados no período selecionado. Altere as datas ou limpe os filtros." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[78rem] border-separate border-spacing-0 text-left">
                        <thead>
                            <tr class="bg-stone-50 text-[0.68rem] font-extrabold uppercase tracking-[0.12em] text-stone-500">
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Data realizada</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Beneficiário</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Tipo de atendimento</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Responsável agendado</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Registrado por</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Prioridade</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Encaminhamentos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach($atendimentos as $atendimento)
                                @php
                                    $prioridade = $atendimento->agendamento?->prioridade;
                                    $urlDetalhes = $atendimento->agendamento
                                        ? route('agendamento.show', $atendimento->agendamento)
                                        : null;
                                @endphp
                                <tr
                                    class="group align-top transition {{ $urlDetalhes ? 'cursor-pointer hover:bg-brand-50/55 focus:bg-brand-50/55 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500' : '' }}"
                                    @if($urlDetalhes)
                                        data-row-link="{{ $urlDetalhes }}"
                                        tabindex="0"
                                        role="link"
                                        aria-label="Ver detalhes do atendimento de {{ $atendimento->beneficiario?->nome_beneficiario ?? 'beneficiário não informado' }}"
                                        title="Abrir detalhes do agendamento"
                                    @endif
                                >
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-stone-800">{{ $atendimento->data_atendimento?->format('d/m/Y') ?? 'Não informada' }}</td>
                                    <td class="px-5 py-4 text-sm">
                                        @if($atendimento->beneficiario)
                                            <span class="font-bold text-brand-800 transition group-hover:text-brand-950">{{ $atendimento->beneficiario->nome_beneficiario }}</span>
                                        @else
                                            <span class="text-stone-500">Não informado</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-sm font-semibold text-stone-700">{{ $atendimento->tipo_atendimento ?? 'Não informado' }}</td>
                                    <td class="px-5 py-4 text-sm text-stone-600">{{ $atendimento->agendamento?->responsavel?->name ?? 'Não definido' }}</td>
                                    <td class="px-5 py-4 text-sm text-stone-600">{{ $atendimento->usuario?->name ?? 'Não informado' }}</td>
                                    <td class="px-5 py-4"><x-ui.badge type="priority" :value="$prioridade" :label="$prioridade ? null : 'Não informada'" /></td>
                                    <td class="max-w-sm whitespace-normal px-5 py-4 text-sm leading-6 text-stone-600">
                                        <div class="flex items-start justify-between gap-3">
                                            <span>{{ filled($atendimento->encaminhamentos) ? $atendimento->encaminhamentos : 'Nenhum' }}</span>
                                            @if($urlDetalhes)
                                                <i data-lucide="chevron-right" class="mt-0.5 size-4 shrink-0 text-stone-300 transition group-hover:translate-x-0.5 group-hover:text-brand-700" aria-hidden="true"></i>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($atendimentos->hasPages())
                    <div class="border-t border-stone-100 px-4 py-3">{{ $atendimentos->withQueryString()->links() }}</div>
                @endif
            @endif
        </section>
    </div>
@endsection
