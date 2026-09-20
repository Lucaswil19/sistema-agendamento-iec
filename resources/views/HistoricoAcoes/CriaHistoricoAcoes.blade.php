@extends('layout.dashboard')

@section('title', 'Relatório de Atendimento')

@section('content')
    <div class="mx-auto max-w-5xl">
        <x-ui.page-header title="Relatório de atendimento" description="Registre o que foi realizado, os encaminhamentos definidos e as informações importantes do atendimento." :breadcrumbs="[['label' => 'Agenda'], ['label' => $agendamento->tipo_acao, 'url' => route('agendamento.show', $agendamento), 'truncate' => true], ['label' => 'Registrar relatório']]" :back-url="route('agendamento.show', $agendamento)" back-label="Voltar ao agendamento" />

        <x-ui.form-errors class="mb-6" />

        <section class="mb-6 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="resumo-atendimento-title">
            <div class="flex items-center gap-3 border-b border-brand-100 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:px-7">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm">
                    <i data-lucide="calendar-days" class="size-5" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 id="resumo-atendimento-title" class="truncate text-lg font-extrabold text-stone-900">{{ $agendamento->tipo_acao }}</h2>
                    <p class="mt-0.5 truncate text-sm text-stone-500">Resumo da ação que será concluída pelo relatório</p>
                </div>
            </div>

            <dl class="grid gap-px bg-stone-100 sm:grid-cols-3">
                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="users" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Beneficiário</dt>
                        <dd class="mt-1 break-words text-sm font-bold text-stone-800">{{ $agendamento->beneficiario->nome_beneficiario }}</dd>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="calendar-days" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Data agendada</dt>
                        <dd class="mt-1 text-sm font-bold text-stone-800">{{ $agendamento->data_agendada->format('d/m/Y') }}</dd>
                    </div>
                </div>

                <div class="flex gap-3 bg-white p-5 sm:p-6">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="clock" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-stone-400">Horário previsto</dt>
                        <dd class="mt-1 text-sm font-bold text-stone-800">
                            {{ substr($agendamento->hora_agendada, 0, 5) }}
                            @if($agendamento->hora_final_agendada)
                                às {{ substr($agendamento->hora_final_agendada, 0, 5) }}
                            @endif
                        </dd>
                    </div>
                </div>
            </dl>
        </section>

        <form method="POST" action="{{ route('historico-acoes.store', $agendamento) }}" class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            @csrf

            <div class="flex items-center gap-3 border-b border-brand-100 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:px-7">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm">
                    <i data-lucide="clipboard-plus" class="size-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 class="text-lg font-extrabold text-stone-900">Dados do atendimento</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Os campos marcados com * são obrigatórios.</p>
                </div>
            </div>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="identificacao-atendimento-title">
                <div class="mb-5 flex items-center gap-3">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="calendar-days" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h3 id="identificacao-atendimento-title" class="font-extrabold text-stone-800">Identificação do atendimento</h3>
                        <p class="text-xs text-stone-500">Informe quando e de que forma o atendimento foi realizado.</p>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="form-floating form-floating-icon">
                        <label for="data_atendimento" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Data do atendimento <span class="text-brand-700" aria-hidden="true">*</span></label>
                        <div class="relative">
                            <i data-lucide="calendar-days" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                            <input type="text" name="data_atendimento" id="data_atendimento" class="form-floating-control h-11 w-full rounded-xl border bg-white pl-11 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 {{ $errors->has('data_atendimento') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" data-mascara="data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" value="{{ old('data_atendimento', now()->format('d/m/Y')) }}" required aria-describedby="data-atendimento-help @error('data_atendimento') data-atendimento-error @enderror" @error('data_atendimento') aria-invalid="true" @enderror>
                        </div>
                        <p id="data-atendimento-help" class="mt-2 text-xs leading-5 text-stone-500">Não pode ser anterior à data agendada nem posterior à data atual.</p>
                        @error('data_atendimento')
                            <p id="data-atendimento-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600"><i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-floating form-floating-icon">
                        <label for="tipo_atendimento" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Tipo de atendimento</label>
                        <div class="relative">
                            <i data-lucide="clipboard-list" class="pointer-events-none absolute left-3.5 top-1/2 size-4.5 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                            <input type="text" name="tipo_atendimento" id="tipo_atendimento" class="form-floating-control h-11 w-full rounded-xl border bg-white pl-11 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 {{ $errors->has('tipo_atendimento') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" maxlength="100" value="{{ old('tipo_atendimento', $agendamento->tipo_acao) }}" placeholder="Ex.: visita, entrega ou orientação" @error('tipo_atendimento') aria-invalid="true" aria-describedby="tipo-atendimento-error" @enderror>
                        </div>
                        @error('tipo_atendimento')
                            <p id="tipo-atendimento-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600"><i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="registro-atendimento-title">
                <div class="mb-5 flex items-center gap-3">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="file-text" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h3 id="registro-atendimento-title" class="font-extrabold text-stone-800">Registro realizado</h3>
                        <p class="text-xs text-stone-500">Descreva objetivamente as atividades e os resultados do atendimento.</p>
                    </div>
                </div>

                <div class="form-floating" style="--floating-label-left: 0.875rem">
                    <label for="descricao" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Descrição do atendimento <span class="text-brand-700" aria-hidden="true">*</span></label>
                    <textarea name="descricao" id="descricao" class="form-floating-control min-h-36 w-full resize-y rounded-xl border bg-white px-3.5 py-3 text-sm leading-6 text-stone-900 outline-none transition placeholder:text-stone-400 {{ $errors->has('descricao') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" rows="5" maxlength="5000" placeholder="Descreva o atendimento realizado, os principais pontos observados e o resultado alcançado" required aria-describedby="descricao-help @error('descricao') descricao-error @enderror" @error('descricao') aria-invalid="true" @enderror>{{ old('descricao') }}</textarea>
                    <div class="mt-2 flex items-start justify-between gap-4 text-xs leading-5 text-stone-500">
                        <p id="descricao-help">Inclua somente informações relevantes para o acompanhamento da ação.</p>
                        <span class="shrink-0">Máximo de 5.000 caracteres</span>
                    </div>
                    @error('descricao')
                        <p id="descricao-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600"><i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="px-5 py-6 sm:px-7" aria-labelledby="informacoes-complementares-title">
                <div class="mb-5 flex items-center gap-3">
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="info" class="size-4.5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h3 id="informacoes-complementares-title" class="font-extrabold text-stone-800">Informações complementares</h3>
                        <p class="text-xs text-stone-500">Registre encaminhamentos e observações quando forem necessários.</p>
                    </div>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="form-floating" style="--floating-label-left: 0.875rem">
                        <label for="encaminhamentos" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Encaminhamentos <span class="font-medium text-stone-400">(opcional)</span></label>
                        <textarea name="encaminhamentos" id="encaminhamentos" class="form-floating-control min-h-28 w-full resize-y rounded-xl border bg-white px-3.5 py-3 text-sm leading-6 text-stone-900 outline-none transition placeholder:text-stone-400 {{ $errors->has('encaminhamentos') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" rows="3" maxlength="5000" placeholder="Providências, orientações ou próximos passos definidos" @error('encaminhamentos') aria-invalid="true" aria-describedby="encaminhamentos-error" @enderror>{{ old('encaminhamentos') }}</textarea>
                        @error('encaminhamentos')
                            <p id="encaminhamentos-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600"><i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-floating" style="--floating-label-left: 0.875rem">
                        <label for="observacoes" class="form-floating-label mb-2 block text-sm font-bold text-stone-700">Observações <span class="font-medium text-stone-400">(opcional)</span></label>
                        <textarea name="observacoes" id="observacoes" class="form-floating-control min-h-28 w-full resize-y rounded-xl border bg-white px-3.5 py-3 text-sm leading-6 text-stone-900 outline-none transition placeholder:text-stone-400 {{ $errors->has('observacoes') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" rows="3" maxlength="5000" placeholder="Outras informações úteis para a equipe" @error('observacoes') aria-invalid="true" aria-describedby="observacoes-error" @enderror>{{ old('observacoes') }}</textarea>
                        @error('observacoes')
                            <p id="observacoes-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600"><i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-700 shadow-sm ring-1 ring-emerald-200">
                            <i data-lucide="check-circle-2" class="size-4.5" aria-hidden="true"></i>
                        </span>
                        <div>
                            <p class="text-sm font-extrabold text-emerald-900">Conclusão do agendamento</p>
                            <p class="mt-1 text-xs leading-5 text-emerald-800">Ao salvar este relatório, o atendimento será marcado como concluído e o registro não poderá ser criado novamente para este agendamento.</p>
                        </div>
                    </div>
                </div>
            </section>

            <x-form.actions :cancel-url="route('agendamento.show', $agendamento)" submit-label="Salvar relatório" submit-icon="clipboard-plus" />
        </form>
    </div>
@endsection
