@extends('layout.dashboard')

@section('title', 'Detalhes do Agendamento')

@section('content')
    @php
        $usuarioAtual = auth()->user();
        $agendamentoAberto = in_array($agendamento->status, ['agendado', 'reagendado'], true);
        $podeEditar = $usuarioAtual->role === 'lider' && $agendamentoAberto;
        $podeCancelar = $usuarioAtual->role === 'lider' && $agendamentoAberto;
        $podeMarcarPerdido = $usuarioAtual->role === 'lider' && $agendamentoAberto && $horarioAgendadoJaPassou;
        $podeReabrir = $usuarioAtual->role === 'lider' && in_array($agendamento->status, ['cancelado', 'perdido'], true);
        $podeRegistrarRelatorio = in_array($usuarioAtual->role, ['lider', 'voluntario'], true) && $agendamentoAberto;
        $possuiAcoes = $podeCancelar || $podeMarcarPerdido || $podeReabrir || $podeRegistrarRelatorio;
        $relatorioFinalizacao = $agendamento->historicoAcoes;

    @endphp

    <div class="mx-auto max-w-6xl">
        <x-ui.page-header title="Detalhes do agendamento" description="Consulte as informações da ação e execute somente as ações disponíveis para o status atual." :breadcrumbs="[['label' => 'Agenda'], ['label' => 'Detalhes do agendamento']]" :back-url="$urlVoltar" :back-label="$textoVoltar">
            @if($podeEditar)
                <x-slot:actions>
                    <a href="{{ route('agendamento.edit', $agendamento) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 text-sm font-bold text-brand-800 transition hover:border-brand-300 hover:bg-brand-100 focus:outline-none focus:ring-4 focus:ring-brand-400/20">
                        <i data-lucide="pencil" class="size-4" aria-hidden="true"></i>
                        Editar
                    </a>
                </x-slot:actions>
            @endif
        </x-ui.page-header>

        <x-ui.form-errors title="Não foi possível concluir a operação:" class="mb-6" />

        <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="resumo-agendamento-title">
            <div class="flex flex-col gap-4 border-b border-brand-100 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm">
                        <i data-lucide="calendar-days" class="size-5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 id="resumo-agendamento-title" class="truncate text-lg font-extrabold text-stone-900">
                            {{ $agendamento->tipo_acao }}
                        </h2>
                        <p class="mt-0.5 truncate text-sm text-stone-500">
                            {{ $agendamento->beneficiario->nome_beneficiario ?? 'Beneficiário não informado' }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-ui.badge type="status" :value="$agendamento->status" icon="check-circle-2" size="large" />
                    <x-ui.badge type="priority" :value="$agendamento->prioridade" :label="'Prioridade '.($agendamento->prioridade === 'media' ? 'Média' : ucfirst($agendamento->prioridade))" icon="triangle-alert" size="large" />
                </div>
            </div>

            <dl class="grid gap-px bg-stone-100 sm:grid-cols-2 xl:grid-cols-3">
                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="users" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Beneficiário</dt>
                        <dd class="mt-1 break-words text-sm font-bold text-stone-800">{{ $agendamento->beneficiario->nome_beneficiario ?? 'Não informado' }}</dd>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="calendar-days" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Data</dt>
                        <dd class="mt-1 text-sm font-bold text-stone-800">{{ $agendamento->data_agendada->format('d/m/Y') }}</dd>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="clock" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Horário</dt>
                        <dd class="mt-1 text-sm font-bold text-stone-800">
                            {{ substr($agendamento->hora_agendada, 0, 5) }}
                            @if($agendamento->hora_final_agendada)
                                às {{ substr($agendamento->hora_final_agendada, 0, 5) }}
                            @endif
                        </dd>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="user-cog" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Responsável</dt>
                        <dd class="mt-1 break-words text-sm font-bold text-stone-800">{{ $agendamento->responsavel->name ?? 'Não definido' }}</dd>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="map-pin" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Local</dt>
                        <dd class="mt-1 break-words text-sm font-bold text-stone-800">{{ $agendamento->local ?: 'Não informado' }}</dd>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="pencil" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Criado por</dt>
                        <dd class="mt-1 break-words text-sm font-bold text-stone-800">{{ $agendamento->criador->name ?? 'Não informado' }}</dd>
                    </div>
                </div>
            </dl>

            <div class="grid gap-5 border-t border-stone-100 p-5 sm:p-7 lg:grid-cols-2">
                <div class="rounded-xl border border-stone-200 bg-stone-50/60 p-4">
                    <div class="flex items-start gap-3">
                        <i data-lucide="file-text" class="mt-0.5 size-5 shrink-0 text-brand-700" aria-hidden="true"></i>
                        <div>
                            <h3 class="text-sm font-extrabold text-stone-800">Observações</h3>
                            <p class="mt-1 whitespace-pre-line text-sm leading-6 text-stone-600">{{ $agendamento->notas ?: 'Nenhuma observação registrada.' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-brand-100 bg-brand-50/70 p-4">
                    <div class="flex items-start gap-3">
                        <i data-lucide="bar-chart-3" class="mt-0.5 size-5 shrink-0 text-brand-700" aria-hidden="true"></i>
                        <div>
                            <h3 class="text-sm font-extrabold text-brand-900">Cálculo da prioridade</h3>
                            <p class="mt-1 text-sm text-brand-900/80">
                                Pontuação: <strong>{{ $agendamento->pontuacao_prioridade }}</strong>
                            </p>
                            @if($agendamento->justificativa_prioridade)
                                <p class="mt-2 text-xs leading-5 text-brand-900/75">{{ $agendamento->justificativa_prioridade }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                @if($agendamento->status === 'cancelado')
                    <div class="rounded-xl border border-red-100 bg-red-50/70 p-4 lg:col-span-2">
                        <div class="flex items-start gap-3">
                            <i data-lucide="circle-alert" class="mt-0.5 size-5 shrink-0 text-red-600" aria-hidden="true"></i>
                            <div>
                                <h3 class="text-sm font-extrabold text-red-800">Motivo do cancelamento</h3>
                                <p class="mt-1 text-sm leading-6 text-red-700">{{ $agendamento->motivo_cancelamento ?: 'Não informado.' }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        @if($agendamento->status === 'completado' && $relatorioFinalizacao)
            <section class="mt-6 overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm" aria-labelledby="relatorio-finalizacao-title">
                <div class="flex flex-col gap-4 border-b border-emerald-100 bg-gradient-to-r from-emerald-50 to-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-700 text-white shadow-sm">
                            <i data-lucide="clipboard-list" class="size-5" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 id="relatorio-finalizacao-title" class="text-lg font-extrabold text-stone-900">Relatório de finalização</h2>
                            <p class="mt-0.5 text-sm text-stone-500">Informações registradas na conclusão deste atendimento.</p>
                        </div>
                    </div>

                    <x-ui.badge type="status" value="completado" label="Atendimento concluído" icon="check-circle-2" size="large" class="w-fit" />
                </div>

                <dl class="grid gap-px bg-stone-100 sm:grid-cols-3">
                    <div class="flex gap-3 bg-white p-5 sm:p-6">
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <i data-lucide="calendar-days" class="size-4.5" aria-hidden="true"></i>
                        </span>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Data realizada</dt>
                            <dd class="mt-1 text-sm font-bold text-stone-800">{{ $relatorioFinalizacao->data_atendimento?->format('d/m/Y') ?? 'Não informada' }}</dd>
                        </div>
                    </div>

                    <div class="flex gap-3 bg-white p-5 sm:p-6">
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <i data-lucide="clipboard-list" class="size-4.5" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0">
                            <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Tipo de atendimento</dt>
                            <dd class="mt-1 break-words text-sm font-bold text-stone-800">{{ $relatorioFinalizacao->tipo_atendimento ?: 'Não informado' }}</dd>
                        </div>
                    </div>

                    <div class="flex gap-3 bg-white p-5 sm:p-6">
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <i data-lucide="user-round" class="size-4.5" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0">
                            <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Registrado por</dt>
                            <dd class="mt-1 break-words text-sm font-bold text-stone-800">{{ $relatorioFinalizacao->usuario?->name ?? 'Não informado' }}</dd>
                        </div>
                    </div>
                </dl>

                <div class="grid gap-5 p-5 sm:p-7 lg:grid-cols-2">
                    <article class="rounded-xl border border-stone-200 bg-stone-50/70 p-4 lg:col-span-2">
                        <div class="flex items-start gap-3">
                            <i data-lucide="file-text" class="mt-0.5 size-5 shrink-0 text-emerald-700" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <h3 class="text-sm font-extrabold text-stone-800">Descrição do atendimento</h3>
                                <p class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-stone-600">{{ $relatorioFinalizacao->descricao }}</p>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-xl border border-stone-200 bg-stone-50/70 p-4">
                        <div class="flex items-start gap-3">
                            <i data-lucide="clipboard-plus" class="mt-0.5 size-5 shrink-0 text-emerald-700" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <h3 class="text-sm font-extrabold text-stone-800">Encaminhamentos</h3>
                                <p class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-stone-600">{{ $relatorioFinalizacao->encaminhamentos ?: 'Nenhum encaminhamento registrado.' }}</p>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-xl border border-stone-200 bg-stone-50/70 p-4">
                        <div class="flex items-start gap-3">
                            <i data-lucide="info" class="mt-0.5 size-5 shrink-0 text-emerald-700" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <h3 class="text-sm font-extrabold text-stone-800">Observações</h3>
                                <p class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-stone-600">{{ $relatorioFinalizacao->observacoes ?: 'Nenhuma observação registrada.' }}</p>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        @endif

        @if($possuiAcoes)
            <section class="mt-6" aria-labelledby="acoes-agendamento-title">
                <div class="mb-4">
                    <h2 id="acoes-agendamento-title" class="text-xl font-extrabold text-stone-900">Ações disponíveis</h2>
                    <p class="mt-1 text-sm text-stone-500">As opções abaixo respeitam seu perfil e o status atual do agendamento.</p>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    @if($podeRegistrarRelatorio)
                        <div class="flex flex-col justify-between rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 shadow-sm">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-700 shadow-sm ring-1 ring-emerald-200">
                                    <i data-lucide="clipboard-plus" class="size-5" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="font-extrabold text-emerald-900">Registrar relatório</h3>
                                    <p class="mt-1 text-sm leading-6 text-emerald-800/80">Registre o resultado do atendimento e conclua a ação social.</p>
                                </div>
                            </div>
                            <a href="{{ route('historico-acoes.create', ['agendamento' => $agendamento->getKey()]) }}" class="mt-5 inline-flex h-10 items-center justify-center gap-2 self-start rounded-xl bg-emerald-700 px-4 text-sm font-bold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-400/25">
                                <i data-lucide="clipboard-plus" class="size-4" aria-hidden="true"></i>
                                Registrar relatório
                            </a>
                        </div>
                    @endif

                    @if($podeCancelar)
                        <form method="POST" action="{{ route('agendamento.cancelar', $agendamento) }}" class="rounded-2xl border border-red-200 bg-red-50/60 p-5 shadow-sm">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="lock_version" value="{{ $agendamento->lock_version }}">

                            <div class="flex items-start gap-3">
                                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-red-600 shadow-sm ring-1 ring-red-200">
                                    <i data-lucide="calendar-x" class="size-5" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="font-extrabold text-red-900">Cancelar agendamento</h3>
                                    <p class="mt-1 text-sm leading-6 text-red-800/75">O motivo é opcional e ficará registrado no histórico.</p>
                                </div>
                            </div>

                            <div class="form-floating mt-4" style="--floating-label-offset: 2.375rem">
                                <label for="motivo_cancelamento" class="form-floating-label mb-2 block text-sm font-bold text-red-900">Motivo do cancelamento</label>
                                <textarea name="motivo_cancelamento" id="motivo_cancelamento" class="form-floating-control min-h-24 w-full resize-y rounded-xl border bg-white px-3 py-2.5 text-sm leading-6 text-stone-900 outline-none transition placeholder:text-stone-400 {{ $errors->has('motivo_cancelamento') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-red-200 hover:border-red-300 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' }}" rows="3" maxlength="2000" placeholder="Informe o motivo, se necessário" @error('motivo_cancelamento') aria-invalid="true" aria-describedby="motivo-cancelamento-error" @enderror>{{ old('motivo_cancelamento') }}</textarea>
                                @error('motivo_cancelamento')
                                    <p id="motivo-cancelamento-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600">
                                        <i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <button type="submit" class="mt-4 inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-red-700 px-4 text-sm font-bold text-white transition hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-400/25">
                                <i data-lucide="calendar-x" class="size-4" aria-hidden="true"></i>
                                Cancelar agendamento
                            </button>
                        </form>
                    @endif

                    @if($podeMarcarPerdido)
                        <form method="POST" action="{{ route('agendamento.marcar-como-perdido', $agendamento) }}" class="rounded-2xl border border-amber-200 bg-amber-50/70 p-5 shadow-sm">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="lock_version" value="{{ $agendamento->lock_version }}">

                            <div class="flex items-start gap-3">
                                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-amber-700 shadow-sm ring-1 ring-amber-200">
                                    <i data-lucide="triangle-alert" class="size-5" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="font-extrabold text-amber-900">Atendimento não realizado</h3>
                                    <p class="mt-1 text-sm leading-6 text-amber-800/80">Use quando o horário já passou e o beneficiário não compareceu.</p>
                                </div>
                            </div>

                            <button type="submit" class="mt-5 inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-amber-300 bg-white px-4 text-sm font-bold text-amber-800 transition hover:bg-amber-100 focus:outline-none focus:ring-4 focus:ring-amber-400/25">
                                <i data-lucide="triangle-alert" class="size-4" aria-hidden="true"></i>
                                Marcar como perdido
                            </button>
                        </form>
                    @endif
                </div>

                @if($podeReabrir)
                    <form method="POST" action="{{ route('agendamento.reabrir', $agendamento) }}" class="mt-4 rounded-2xl border border-brand-200 bg-white p-5 shadow-sm sm:p-6">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="lock_version" value="{{ $agendamento->lock_version }}">

                        <div class="flex items-start gap-3">
                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                                <i data-lucide="rotate-ccw" class="size-5" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h3 class="font-extrabold text-stone-900">Reabrir e reagendar</h3>
                                <p class="mt-1 text-sm leading-6 text-stone-500">Informe uma nova data e horários futuros. O motivo do cancelamento anterior será removido.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-5 md:grid-cols-3">
                            <div class="form-floating form-floating-icon">
                                <label for="data_reabertura" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Nova data <span class="text-brand-700" aria-hidden="true">*</span></label>
                                <div class="relative">
                                    <i data-lucide="calendar-days" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                                    <input type="text" name="data_agendada" id="data_reabertura" class="form-floating-control h-11 w-full rounded-xl border bg-white pl-11 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 {{ $errors->has('data_agendada') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" value="{{ old('data_agendada', $agendamento->data_agendada->format('d/m/Y')) }}" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" data-mascara="data" required @error('data_agendada') aria-invalid="true" aria-describedby="data-reabertura-error" @enderror>
                                </div>
                                @error('data_agendada')
                                    <p id="data-reabertura-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600"><i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-floating form-floating-icon">
                                <label for="hora_reabertura" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Horário inicial <span class="text-brand-700" aria-hidden="true">*</span></label>
                                <div class="relative">
                                    <i data-lucide="clock" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                                    <input type="text" name="hora_agendada" id="hora_reabertura" class="form-floating-control h-11 w-full rounded-xl border bg-white pl-11 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 {{ $errors->has('hora_agendada') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" value="{{ old('hora_agendada', substr($agendamento->hora_agendada, 0, 5)) }}" placeholder="hh:mm" maxlength="5" inputmode="numeric" data-mascara="hora" required @error('hora_agendada') aria-invalid="true" aria-describedby="hora-reabertura-error" @enderror>
                                </div>
                                @error('hora_agendada')
                                    <p id="hora-reabertura-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600"><i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-floating form-floating-icon">
                                <label for="hora_final_reabertura" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Horário final</label>
                                <div class="relative">
                                    <i data-lucide="clock" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                                    <input type="text" name="hora_final_agendada" id="hora_final_reabertura" class="form-floating-control h-11 w-full rounded-xl border bg-white pl-11 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 {{ $errors->has('hora_final_agendada') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" value="{{ old('hora_final_agendada', $agendamento->hora_final_agendada ? substr($agendamento->hora_final_agendada, 0, 5) : '') }}" placeholder="hh:mm" maxlength="5" inputmode="numeric" data-mascara="hora" @error('hora_final_agendada') aria-invalid="true" aria-describedby="hora-final-reabertura-error" @enderror>
                                </div>
                                @error('hora_final_agendada')
                                    <p id="hora-final-reabertura-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600"><i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="mt-5 inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 px-5 text-sm font-bold text-white shadow-[0_10px_24px_-12px_rgba(240,64,0,0.9)] transition hover:from-brand-800 hover:via-brand-700 hover:to-brand-600 focus:outline-none focus:ring-4 focus:ring-brand-400/30">
                            <i data-lucide="rotate-ccw" class="size-4" aria-hidden="true"></i>
                            Reabrir agendamento
                        </button>
                    </form>
                @endif
            </section>
        @endif
    </div>
@endsection
