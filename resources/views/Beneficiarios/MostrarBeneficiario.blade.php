@extends('layout.dashboard')

@section('title', 'Detalhes do Beneficiário')

@section('content')
    @php
        $usuarioAtual = auth()->user();
        $ehLider = $usuarioAtual->role === 'lider';
        $totalDependentes = $historicoFamiliar->total();
        $totalFamilia = $totalDependentes + 1;
    @endphp

    <div class="mx-auto max-w-7xl">
        <x-ui.page-header title="Detalhes do beneficiário" description="Consulte os dados pessoais, a composição familiar e todo o histórico de atendimento vinculado." :breadcrumbs="[['label' => 'Beneficiários', 'url' => route('home')], ['label' => 'Detalhes']]" :back-url="route('home')">
            @if($ehLider)
                <x-slot:actions>
                    @if($beneficiario->is_active)
                        <a href="{{ route('agendamento.create', ['beneficiario_id' => $beneficiario->id]) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/30">
                            <i data-lucide="calendar-plus" class="size-4" aria-hidden="true"></i>
                            Criar agendamento
                        </a>
                    @else
                        <form method="POST" action="{{ route('beneficiarios.reativar', $beneficiario) }}">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-400/30">
                                <i data-lucide="archive-restore" class="size-4" aria-hidden="true"></i>
                                Reativar beneficiário
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('beneficiarios.edit', $beneficiario) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 text-sm font-bold text-brand-800 transition hover:border-brand-300 hover:bg-brand-100 focus:outline-none focus:ring-4 focus:ring-brand-400/20">
                        <i data-lucide="pencil" class="size-4" aria-hidden="true"></i>
                        Editar
                    </a>
                </x-slot:actions>
            @endif
        </x-ui.page-header>

        <x-ui.form-errors title="Não foi possível concluir a operação:" class="mb-6" />

        <section class="mb-6 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="resumo-beneficiario-title">
            <div class="flex flex-col gap-5 border-b border-brand-100 bg-gradient-to-r from-brand-50 via-white to-white px-5 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="inline-flex size-14 shrink-0 items-center justify-center rounded-2xl bg-brand-700 text-white shadow-sm">
                        <i data-lucide="user-round" class="size-7" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 id="resumo-beneficiario-title" class="truncate text-xl font-extrabold text-stone-900 sm:text-2xl">
                            {{ $beneficiario->nome_beneficiario }}
                        </h2>
                        <p class="mt-1 text-sm text-stone-500">CPF {{ $beneficiario->cpf ?: 'não informado' }}</p>
                    </div>
                </div>

                <x-ui.badge type="situation" :value="$beneficiario->is_active" :label="$beneficiario->is_active ? 'Beneficiário ativo' : 'Beneficiário arquivado'" :icon="$beneficiario->is_active ? 'check-circle-2' : 'archive-restore'" size="large" class="w-fit" />
            </div>

            <dl class="grid gap-px bg-stone-100 sm:grid-cols-3">
                <div class="flex items-center gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="users" class="size-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Família</dt>
                        <dd class="mt-1 text-sm font-extrabold text-stone-800">{{ $totalFamilia }} {{ $totalFamilia === 1 ? 'pessoa' : 'pessoas' }}</dd>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="contact-round" class="size-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Dependentes</dt>
                        <dd class="mt-1 text-sm font-extrabold text-stone-800">{{ $totalDependentes }} {{ $totalDependentes === 1 ? 'cadastrado' : 'cadastrados' }}</dd>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="calendar-days" class="size-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Agendamentos</dt>
                        <dd class="mt-1 text-sm font-extrabold text-stone-800">{{ $agendamentos->total() }} {{ $agendamentos->total() === 1 ? 'registro' : 'registros' }}</dd>
                    </div>
                </div>
            </dl>
        </section>

        <section class="mb-6 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="dados-beneficiario-title">
            <div class="flex items-center gap-3 border-b border-stone-100 px-5 py-5 sm:px-7">
                <span class="inline-flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                    <i data-lucide="contact-round" class="size-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 id="dados-beneficiario-title" class="text-lg font-extrabold text-stone-900">Dados do beneficiário</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Informações pessoais, contato e condições declaradas.</p>
                </div>
            </div>

            <div class="grid gap-px bg-stone-100 md:grid-cols-2 xl:grid-cols-3">
                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-stone-600">
                        <i data-lucide="phone" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Telefone</p>
                        <p class="mt-1 break-words text-sm font-bold text-stone-800">{{ $beneficiario->telefone ?: 'Não informado' }}</p>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-stone-600">
                        <i data-lucide="mail" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-stone-400">E-mail</p>
                        <p class="mt-1 break-all text-sm font-bold text-stone-800">{{ $beneficiario->email ?: 'Não informado' }}</p>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-stone-600">
                        <i data-lucide="map-pin" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-stone-400">CEP</p>
                        <p class="mt-1 break-words text-sm font-bold text-stone-800">{{ $beneficiario->cep ?: 'Não informado' }}</p>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-stone-600">
                        <i data-lucide="map-pin" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Endereço</p>
                        <p class="mt-1 break-words text-sm font-bold leading-6 text-stone-800">{{ $beneficiario->endereco ?: 'Não informado' }}</p>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6" data-beneficiario-field="data-nascimento">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-stone-600">
                        <i data-lucide="cake" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Data de nascimento</p>
                        <p class="mt-1 text-sm font-bold text-stone-800">{{ $beneficiario->data_nascimento ? $beneficiario->data_nascimento->format('d/m/Y') : 'Não informada' }}</p>
                        <p class="mt-0.5 text-xs text-stone-500">{{ $beneficiario->idade !== null ? $beneficiario->idade . ' anos' : 'Idade não informada' }}</p>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl {{ $beneficiario->possui_problema_saude ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                        <i data-lucide="heart-pulse" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Problema de saúde</p>
                        <p class="mt-1 text-sm font-bold text-stone-800">{{ $beneficiario->possui_problema_saude ? 'Sim' : 'Não' }}</p>
                        @if($beneficiario->possui_problema_saude)
                            <p class="mt-1 break-words text-xs leading-5 text-stone-500">{{ $beneficiario->descricao_problema_saude ?: 'Sem descrição.' }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl {{ $beneficiario->possui_deficiencia ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                        <i data-lucide="accessibility" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-stone-400">Deficiência</p>
                        <p class="mt-1 text-sm font-bold text-stone-800">{{ $beneficiario->possui_deficiencia ? 'Sim' : 'Não' }}</p>
                        @if($beneficiario->possui_deficiencia)
                            <p class="mt-1 break-words text-xs leading-5 text-stone-500">{{ $beneficiario->descricao_deficiencia ?: 'Sem descrição.' }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-t border-stone-100 p-5 sm:p-7">
                <div class="rounded-xl border border-stone-200 bg-stone-50/70 p-4" data-beneficiario-field="observacoes">
                    <div class="flex items-start gap-3">
                        <i data-lucide="file-text" class="mt-0.5 size-5 shrink-0 text-brand-700" aria-hidden="true"></i>
                        <div>
                            <h3 class="text-sm font-extrabold text-stone-800">Observações</h3>
                            <p class="mt-1 whitespace-pre-line text-sm leading-6 text-stone-600">{{ $beneficiario->observacoes ?: 'Nenhuma observação registrada.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-6 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="historico-familiar-title">
            <div class="flex flex-col gap-4 border-b border-stone-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="users" class="size-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 id="historico-familiar-title" class="text-lg font-extrabold text-stone-900">Histórico familiar</h2>
                        <p class="mt-0.5 text-sm text-stone-500">{{ $totalDependentes }} {{ $totalDependentes === 1 ? 'membro familiar cadastrado' : 'membros familiares cadastrados' }}</p>
                    </div>
                </div>

                @if($ehLider && $beneficiario->is_active)
                    <a href="{{ route('historico-familiar.create', $beneficiario) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/30">
                        <i data-lucide="plus" class="size-4" aria-hidden="true"></i>
                        {{ $totalDependentes === 0 ? 'Adicionar histórico familiar' : 'Adicionar membro familiar' }}
                    </a>
                @endif
            </div>

            @if($historicoFamiliar->isEmpty())
                <x-ui.empty-state title="Nenhum membro familiar cadastrado" description="Adicione os membros da família para manter o histórico e o cálculo de prioridade atualizados." icon="users" class="py-14" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[76rem] border-separate border-spacing-0 text-left">
                        <thead>
                            <tr class="bg-stone-50 text-[0.68rem] font-extrabold uppercase tracking-[0.12em] text-stone-500">
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Nome</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Parentesco</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Nascimento</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Classificação</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Saúde</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Deficiência</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Observações</th>
                                @if($ehLider)
                                    <th scope="col" class="border-b border-stone-200 px-5 py-3.5"><span class="sr-only">Ações</span></th>
                                @endif
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-stone-100">
                            @foreach($historicoFamiliar as $historico)
                                @php
                                    $idade = $historico->idade;
                                    $rotuloClassificacao = $idade === null ? 'Não informado' : ($idade <= 12 ? 'Criança' : ($idade >= 60 ? 'Idoso' : 'Adulto'));
                                    $classesClassificacao = $idade === null
                                        ? 'bg-stone-100 text-stone-600 ring-stone-200'
                                        : ($idade <= 12
                                            ? 'bg-sky-50 text-sky-700 ring-sky-200'
                                            : ($idade >= 60
                                                ? 'bg-violet-50 text-violet-700 ring-violet-200'
                                                : 'bg-brand-50 text-brand-800 ring-brand-200'));
                                @endphp

                                <tr class="transition hover:bg-brand-50/40">
                                    <td class="px-5 py-4 align-top">
                                        <p class="max-w-48 truncate text-sm font-bold text-stone-800" title="{{ $historico->nome }}">{{ $historico->nome }}</p>
                                        <p class="mt-0.5 text-xs text-stone-500">{{ $idade !== null ? $idade . ' anos' : 'Idade não informada' }}</p>
                                    </td>
                                    <td class="px-5 py-4 align-top text-sm text-stone-600">{{ $historico->parentesco ?: 'Não informado' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 align-top text-sm text-stone-600">{{ $historico->data_nascimento ? $historico->data_nascimento->format('d/m/Y') : 'Não informada' }}</td>
                                    <td class="px-5 py-4 align-top">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $classesClassificacao }}">{{ $rotuloClassificacao }}</span>
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $historico->possui_problema_saude ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">{{ $historico->possui_problema_saude ? 'Sim' : 'Não' }}</span>
                                        @if($historico->possui_problema_saude)
                                            <p class="mt-1.5 max-w-52 text-xs leading-5 text-stone-500">{{ $historico->descricao_problema_saude ?: 'Sem descrição.' }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $historico->possui_deficiencia ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200' }}">{{ $historico->possui_deficiencia ? 'Sim' : 'Não' }}</span>
                                        @if($historico->possui_deficiencia)
                                            <p class="mt-1.5 max-w-52 text-xs leading-5 text-stone-500">{{ $historico->descricao_deficiencia ?: 'Sem descrição.' }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 align-top text-sm leading-6 text-stone-600">
                                        <p class="max-w-56">{{ $historico->observacoes ?: 'Nenhuma observação.' }}</p>
                                    </td>
                                    @if($ehLider)
                                        <td class="px-5 py-4 align-top">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <a href="{{ route('historico-familiar.edit', [$beneficiario, $historico]) }}" class="inline-flex size-9 items-center justify-center rounded-lg text-stone-500 transition hover:bg-white hover:text-brand-800 hover:shadow-sm focus:outline-none focus:ring-4 focus:ring-brand-400/20" title="Editar membro familiar" aria-label="Editar membro familiar">
                                                    <i data-lucide="pencil" class="size-4" aria-hidden="true"></i>
                                                </a>

                                                <form action="{{ route('historico-familiar.destroy', [$beneficiario, $historico]) }}" method="POST" onsubmit="return confirm('Deseja realmente excluir este registro familiar?')">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit" class="inline-flex size-9 items-center justify-center rounded-lg text-stone-500 transition hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-4 focus:ring-red-400/20" title="Excluir membro familiar" aria-label="Excluir membro familiar">
                                                        <i data-lucide="trash-2" class="size-4" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($historicoFamiliar->hasPages())
                    <div class="border-t border-stone-100 px-5 py-4">{{ $historicoFamiliar->links() }}</div>
                @endif
            @endif
        </section>

        <section class="mb-6 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="agendamentos-beneficiario-title">
            <div class="flex items-center justify-between gap-4 border-b border-stone-100 px-5 py-5 sm:px-7">
                <div class="flex items-center gap-3">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="calendar-days" class="size-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 id="agendamentos-beneficiario-title" class="text-lg font-extrabold text-stone-900">Agendamentos vinculados</h2>
                        <p class="mt-0.5 text-sm text-stone-500">{{ $agendamentos->total() }} {{ $agendamentos->total() === 1 ? 'agendamento encontrado' : 'agendamentos encontrados' }}</p>
                    </div>
                </div>
            </div>

            @if($agendamentos->isEmpty())
                <x-ui.empty-state title="Nenhum agendamento cadastrado" description="Ainda não existem ações vinculadas a este beneficiário." icon="calendar-days" class="py-14" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[52rem] border-separate border-spacing-0 text-left">
                        <thead>
                            <tr class="bg-stone-50 text-[0.68rem] font-extrabold uppercase tracking-[0.12em] text-stone-500">
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Data</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Tipo de ação</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Prioridade</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Status</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-stone-100">
                            @foreach($agendamentos as $agendamento)
                                <tr class="group transition hover:bg-brand-50/45">
                                    <td class="p-0 text-sm font-bold text-stone-800">
                                        <a href="{{ route('agendamento.show', $agendamento) }}" data-detail-link="agendamento" class="block whitespace-nowrap px-5 py-4 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" title="Ver detalhes do agendamento">{{ $agendamento->data_agendada ? $agendamento->data_agendada->format('d/m/Y') : 'Não informada' }}</a>
                                    </td>
                                    <td class="p-0 text-sm">
                                        <a href="{{ route('agendamento.show', $agendamento) }}" data-detail-link="agendamento" class="block max-w-72 truncate px-5 py-4 font-bold text-brand-800 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" title="{{ $agendamento->tipo_acao }}">{{ $agendamento->tipo_acao }}</a>
                                    </td>
                                    <td class="p-0">
                                        <a href="{{ route('agendamento.show', $agendamento) }}" data-detail-link="agendamento" class="block px-5 py-4 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" title="Ver detalhes do agendamento">
                                            <x-ui.badge type="priority" :value="$agendamento->prioridade" />
                                        </a>
                                    </td>
                                    <td class="p-0">
                                        <a href="{{ route('agendamento.show', $agendamento) }}" data-detail-link="agendamento" class="block px-5 py-4 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500" title="Ver detalhes do agendamento">
                                            <x-ui.badge type="status" :value="$agendamento->status" />
                                        </a>
                                    </td>
                                    <td class="px-5 py-4">
                                        <a href="{{ route('agendamento.show', $agendamento) }}" class="ml-auto inline-flex size-9 items-center justify-center rounded-lg text-stone-500 transition hover:bg-white hover:text-brand-800 hover:shadow-sm focus:outline-none focus:ring-4 focus:ring-brand-400/20" title="Ver agendamento" aria-label="Ver agendamento">
                                            <i data-lucide="eye" class="size-4" aria-hidden="true"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($agendamentos->hasPages())
                    <div class="border-t border-stone-100 px-5 py-4">{{ $agendamentos->links() }}</div>
                @endif
            @endif
        </section>

        @if($historicoAcoes->isNotEmpty())
            <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="historico-acoes-title">
                <div class="flex items-center gap-3 border-b border-stone-100 px-5 py-5 sm:px-7">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="history" class="size-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 id="historico-acoes-title" class="text-lg font-extrabold text-stone-900">Histórico de ações realizadas</h2>
                        <p class="mt-0.5 text-sm text-stone-500">Registros de atendimentos já executados para este beneficiário.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[68rem] border-separate border-spacing-0 text-left">
                        <thead>
                            <tr class="bg-stone-50 text-[0.68rem] font-extrabold uppercase tracking-[0.12em] text-stone-500">
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Data</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Tipo de atendimento</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Descrição</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Encaminhamentos</th>
                                <th scope="col" class="border-b border-stone-200 px-5 py-3.5">Observações</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-stone-100">
                            @foreach($historicoAcoes as $acao)
                                <tr class="transition hover:bg-brand-50/40">
                                    <td class="whitespace-nowrap px-5 py-4 align-top text-sm font-bold text-stone-800">{{ $acao->data_atendimento ? $acao->data_atendimento->format('d/m/Y') : 'Não informada' }}</td>
                                    <td class="px-5 py-4 align-top text-sm font-semibold text-brand-800">{{ $acao->tipo_atendimento ?: 'Não informado' }}</td>
                                    <td class="px-5 py-4 align-top text-sm leading-6 text-stone-600"><p class="max-w-72">{{ $acao->descricao }}</p></td>
                                    <td class="px-5 py-4 align-top text-sm leading-6 text-stone-600"><p class="max-w-72">{{ $acao->encaminhamentos ?: 'Nenhum encaminhamento registrado.' }}</p></td>
                                    <td class="px-5 py-4 align-top text-sm leading-6 text-stone-600"><p class="max-w-72">{{ $acao->observacoes ?: 'Nenhuma observação.' }}</p></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($historicoAcoes->hasPages())
                    <div class="border-t border-stone-100 px-5 py-4">{{ $historicoAcoes->links() }}</div>
                @endif
            </section>
        @endif
    </div>
@endsection
