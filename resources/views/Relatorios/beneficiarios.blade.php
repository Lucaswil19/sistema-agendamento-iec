@extends('layout.dashboard')

@section('title', 'Relatório de Beneficiários')

@section('content')
    @php
        $beneficiariosNaPagina = $beneficiarios->count();
        $pessoasRepresentadasNaPagina = $beneficiarios->sum(fn ($beneficiario) => $beneficiario->numero_familiares + 1);
        $acoesNaPagina = $beneficiarios->sum('numero_acoes');
    @endphp

    <div class="mx-auto max-w-[100rem]">
        <x-ui.page-header title="Relatório de beneficiários" description="Consulte os beneficiários cadastrados, a composição familiar e o histórico de ações recebidas." :breadcrumbs="[['label' => 'Relatórios'], ['label' => 'Beneficiários']]" :back-url="route('home')" back-label="Voltar para beneficiários" />

        <x-ui.form-errors title="Não foi possível carregar o relatório:" class="mb-6" />

        <x-ui.alert type="info" title="Como a composição familiar é calculada" class="mb-6">
            <p class="leading-6 text-brand-900/80">O número de membros inclui o próprio beneficiário e todos os familiares cadastrados no seu histórico familiar.</p>
        </x-ui.alert>

        <section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores do relatório">
            <x-ui.stat-card label="Total de beneficiários" :value="$beneficiarios->total()" icon="contact-round" description="Cadastros disponíveis no relatório" />
            <x-ui.stat-card label="Exibidos nesta página" :value="$beneficiariosNaPagina" icon="user-round" tone="sky" description="Máximo de 20 registros por página" />
            <x-ui.stat-card label="Pessoas representadas" :value="$pessoasRepresentadasNaPagina" icon="users" tone="violet" description="Beneficiários e familiares nesta página" />
            <x-ui.stat-card label="Ações registradas" :value="$acoesNaPagina" icon="check-circle-2" tone="emerald" description="Total recebido pelos registros desta página" />
        </section>

        <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="lista-beneficiarios-relatorio-title">
            <div class="flex items-center gap-3 border-b border-stone-100 px-5 py-5 sm:px-7">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><i data-lucide="clipboard-list" class="size-5" aria-hidden="true"></i></span>
                <div>
                    <h2 id="lista-beneficiarios-relatorio-title" class="text-lg font-extrabold text-stone-900">Beneficiários cadastrados</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Composição familiar e ações efetivamente registradas.</p>
                </div>
            </div>

            @if($beneficiarios->isEmpty())
                <x-ui.empty-state title="Nenhum beneficiário cadastrado" description="Os cadastros aparecerão neste relatório assim que forem incluídos no sistema." icon="users" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[58rem] border-separate border-spacing-0 text-left">
                        <thead>
                            <tr class="bg-stone-50 text-[0.68rem] font-extrabold uppercase tracking-[0.12em] text-stone-500">
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Beneficiário</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5 text-center">Membros da família</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5 text-center">Última ação</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5 text-center">Ações recebidas</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach($beneficiarios as $beneficiario)
                                <tr class="transition hover:bg-brand-50/40">
                                    <td class="px-5 py-4">
                                        <a href="{{ route('beneficiarios.show', $beneficiario) }}" class="flex min-w-0 items-center gap-3 rounded-lg outline-none focus:ring-4 focus:ring-brand-400/20">
                                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-700 to-brand-400 text-sm font-extrabold text-white shadow-sm" aria-hidden="true">{{ mb_strtoupper(mb_substr($beneficiario->nome_beneficiario, 0, 1)) }}</span>
                                            <span class="truncate text-sm font-bold text-brand-800 transition hover:text-brand-950 hover:underline">{{ $beneficiario->nome_beneficiario }}</span>
                                        </a>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex min-w-10 items-center justify-center rounded-full bg-violet-50 px-2.5 py-1 text-xs font-extrabold text-violet-700 ring-1 ring-inset ring-violet-200">{{ $beneficiario->numero_familiares + 1 }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-center text-sm">
                                        @if($beneficiario->data_ultima_acao)
                                            <span class="inline-flex items-center gap-2 font-semibold text-stone-700"><i data-lucide="calendar-days" class="size-4 text-stone-400" aria-hidden="true"></i>{{ $beneficiario->data_ultima_acao->format('d/m/Y') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-stone-100 px-2.5 py-1 text-xs font-bold text-stone-500 ring-1 ring-inset ring-stone-200">Nenhuma ação</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex min-w-10 items-center justify-center rounded-full px-2.5 py-1 text-xs font-extrabold ring-1 ring-inset {{ $beneficiario->numero_acoes > 0 ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-stone-100 text-stone-500 ring-stone-200' }}">{{ $beneficiario->numero_acoes }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('beneficiarios.show', $beneficiario) }}" class="inline-flex size-9 items-center justify-center rounded-lg text-stone-500 transition hover:bg-white hover:text-brand-800 hover:shadow-sm focus:outline-none focus:ring-4 focus:ring-brand-400/20" title="Ver ficha do beneficiário" aria-label="Ver ficha de {{ $beneficiario->nome_beneficiario }}"><i data-lucide="eye" class="size-4" aria-hidden="true"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($beneficiarios->hasPages())
                    <div class="border-t border-stone-100 px-4 py-3">{{ $beneficiarios->withQueryString()->links() }}</div>
                @endif
            @endif
        </section>
    </div>
@endsection
