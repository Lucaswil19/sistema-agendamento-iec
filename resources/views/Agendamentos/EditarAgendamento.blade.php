@extends('layout.dashboard')

@section('title', 'Editar Agendamento')

@section('content')
    @php
        $beneficiarioAtual = (string) old('beneficiario_id', $agendamento->beneficiario_id);
        $responsavelAtual = (string) old('responsavel_id', $agendamento->responsavel_id ?? '');
        $beneficiarioAtualModel = $beneficiarios->first(
            fn ($beneficiario) => (string) $beneficiario->id === $beneficiarioAtual
        );
        $responsavelAtualModel = $responsaveis->first(
            fn ($responsavel) => (string) $responsavel->id === $responsavelAtual
        );
        $rotulosPerfis = [
            'lider' => 'Líder',
            'secretaria' => 'Secretaria',
            'voluntario' => 'Voluntário',
        ];
        $rotuloPrioridade = match ($agendamento->prioridade) {
            'baixa' => 'Baixa',
            'media' => 'Média',
            'alta' => 'Alta',
            'urgente' => 'Urgente',
            default => ucfirst($agendamento->prioridade),
        };
    @endphp

    <div class="mx-auto max-w-5xl">
        <x-ui.page-header title="Editar agendamento" description="Atualize os dados da ação social. O status deve ser alterado pelas ações específicas do agendamento." :breadcrumbs="[['label' => 'Agenda'], ['label' => 'Editar agendamento']]" :back-url="route('agendamento.index')" back-label="Voltar à agenda">
            <x-slot:badge>
                <x-ui.badge type="status" :value="$agendamento->status" icon="calendar-days" />
            </x-slot:badge>
        </x-ui.page-header>

        <x-ui.form-errors title="Verifique os campos abaixo:" class="mb-6" />

        <form method="POST" action="{{ route('agendamento.update', $agendamento) }}" class="rounded-2xl border border-stone-200 bg-white shadow-sm">
            @csrf
            @method('PUT')
            <input type="hidden" name="lock_version" value="{{ $agendamento->lock_version }}">

            <div class="flex items-center gap-3 rounded-t-2xl border-b border-brand-100 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:px-7">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm">
                    <i data-lucide="pencil" class="size-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 class="text-lg font-extrabold text-stone-900">Informações do agendamento</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Os campos marcados com * são obrigatórios.</p>
                </div>
            </div>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="edicao-atendimento-title">
                <x-form.section-heading id="edicao-atendimento-title" icon="users" title="Atendimento" description="Beneficiário atendido e tipo de ação social." />

                <div class="grid gap-5">
                    <div>
                        <label for="beneficiario_id" id="editar-beneficiario-label" class="mb-2 block text-sm font-bold text-stone-700">
                            Beneficiário <span class="text-brand-700" aria-hidden="true">*</span>
                        </label>
                        <div class="relative" data-custom-select>
                            <select name="beneficiario_id" id="beneficiario_id" data-custom-select-native class="h-11 w-full appearance-none rounded-xl border bg-white px-3 pr-9 text-sm text-stone-900 outline-none transition {{ $errors->has('beneficiario_id') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" required @error('beneficiario_id') aria-invalid="true" aria-describedby="editar-beneficiario-error" @enderror>
                                @foreach($beneficiarios as $beneficiario)
                                    <option value="{{ $beneficiario->id }}" @selected($beneficiarioAtual === (string) $beneficiario->id)>
                                        {{ $beneficiario->nome_beneficiario }} - CPF: {{ $beneficiario->cpf }}{{ !$beneficiario->is_active ? ' — Inativo' : '' }}
                                    </option>
                                @endforeach
                            </select>

                            <button type="button" id="editar-beneficiario-trigger" data-custom-select-trigger class="hidden h-11 w-full min-w-0 items-center justify-between gap-3 rounded-xl border bg-white px-3 text-left text-sm text-stone-900 outline-none transition {{ $errors->has('beneficiario_id') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" aria-haspopup="listbox" aria-expanded="false" aria-controls="editar-beneficiario-options" aria-labelledby="editar-beneficiario-label editar-beneficiario-value" aria-describedby="editar-beneficiario-required-error @error('beneficiario_id') editar-beneficiario-error @enderror" @error('beneficiario_id') aria-invalid="true" @enderror>
                                <span id="editar-beneficiario-value" data-custom-select-value class="truncate">
                                    @if($beneficiarioAtualModel)
                                        {{ $beneficiarioAtualModel->nome_beneficiario }} - CPF: {{ $beneficiarioAtualModel->cpf }}{{ !$beneficiarioAtualModel->is_active ? ' — Inativo' : '' }}
                                    @else
                                        Selecione um beneficiário
                                    @endif
                                </span>
                                <i data-lucide="chevron-down" data-custom-select-chevron class="size-4 shrink-0 text-stone-400 transition-transform" aria-hidden="true"></i>
                            </button>

                            <div data-custom-select-options class="absolute left-0 right-0 top-full z-30 mt-2 hidden overflow-hidden rounded-xl border border-brand-100 bg-white shadow-[0_18px_45px_-18px_rgba(86,20,5,0.35)]">
                                <div class="border-b border-stone-100 p-2">
                                    <div class="relative form-floating form-floating-search">
                                        <label for="editar-beneficiario-search" class="form-floating-label text-sm font-bold text-stone-700">Pesquisar beneficiário</label>
                                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                                        <input type="search" id="editar-beneficiario-search" data-custom-select-search class="form-floating-control h-10 w-full rounded-lg border border-stone-300 bg-white pl-9 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20" placeholder="Pesquisar por nome ou CPF" autocomplete="off" spellcheck="false" enterkeyhint="search" aria-controls="editar-beneficiario-options">
                                    </div>
                                </div>
                                <div id="editar-beneficiario-options" class="max-h-56 overflow-y-auto p-1.5" role="listbox" aria-labelledby="editar-beneficiario-label">
                                    @foreach($beneficiarios as $beneficiario)
                                        <button type="button" data-custom-select-option data-value="{{ $beneficiario->id }}" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-stone-700 outline-none transition hover:bg-brand-50 hover:text-brand-800 focus:bg-brand-50 focus:text-brand-800" role="option" aria-selected="{{ $beneficiarioAtual === (string) $beneficiario->id ? 'true' : 'false' }}" tabindex="-1">
                                            <span>{{ $beneficiario->nome_beneficiario }} - CPF: {{ $beneficiario->cpf }}{{ !$beneficiario->is_active ? ' — Inativo' : '' }}</span>
                                            <i data-lucide="check" data-custom-select-check class="size-4 shrink-0 text-brand-600 {{ $beneficiarioAtual === (string) $beneficiario->id ? '' : 'hidden' }}" aria-hidden="true"></i>
                                        </button>
                                    @endforeach
                                </div>
                                <p id="editar-beneficiario-empty" data-custom-select-empty class="hidden px-3 py-5 text-center text-sm font-medium text-stone-500" role="status" aria-live="polite" aria-hidden="true">
                                    Nenhum beneficiário encontrado.
                                </p>
                            </div>
                        </div>
                        <p id="editar-beneficiario-required-error" data-custom-select-required-error class="mt-2 hidden items-center gap-1.5 text-xs font-semibold text-red-600" role="alert">
                            <i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>
                            Selecione o beneficiário que receberá o atendimento.
                        </p>
                        @error('beneficiario_id')
                            <p id="editar-beneficiario-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600">
                                <i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <x-form.input name="tipo_acao" label="Tipo de ação" icon="clipboard-list" :value="$agendamento->tipo_acao" maxlength="100" required />
                </div>
            </section>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="edicao-data-horario-title">
                <x-form.section-heading id="edicao-data-horario-title" icon="calendar-days" title="Data e horário" description="Atualize quando a ação social acontecerá." />

                <div class="grid gap-5 md:grid-cols-3">
                    <x-form.input name="data_agendada" label="Data" icon="calendar-days" :value="$agendamento->data_agendada?->format('d/m/Y')" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" data-mascara="data" help="Exemplo: 15/07/2026" required />
                    <x-form.input name="hora_agendada" label="Horário inicial" icon="clock" :value="$agendamento->hora_agendada ? substr($agendamento->hora_agendada, 0, 5) : ''" placeholder="hh:mm" maxlength="5" inputmode="numeric" data-mascara="hora" help="Exemplo: 14:30" required />
                    <x-form.input name="hora_final_agendada" label="Horário final" icon="clock" :value="$agendamento->hora_final_agendada ? substr($agendamento->hora_final_agendada, 0, 5) : ''" placeholder="hh:mm" maxlength="5" inputmode="numeric" data-mascara="hora" help="Deve ser posterior ao início." />
                </div>
            </section>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="edicao-responsabilidade-title">
                <x-form.section-heading id="edicao-responsabilidade-title" icon="user-cog" title="Responsabilidade e prioridade" description="Responsável atual e prioridade calculada pelo sistema." />

                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <label for="responsavel_id" id="editar-responsavel-label" class="mb-2 block text-sm font-bold text-stone-700">
                            Responsável pela ação <span class="text-brand-700" aria-hidden="true">*</span>
                        </label>
                        <div class="relative" data-custom-select>
                            <select name="responsavel_id" id="responsavel_id" data-custom-select-native class="h-11 w-full appearance-none rounded-xl border bg-white px-3 pr-9 text-sm text-stone-900 outline-none transition {{ $errors->has('responsavel_id') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" required @error('responsavel_id') aria-invalid="true" aria-describedby="editar-responsavel-error" @enderror>
                                <option value="">Selecione um responsável</option>
                                @foreach($responsaveis as $responsavel)
                                    <option value="{{ $responsavel->id }}" @selected($responsavelAtual === (string) $responsavel->id)>
                                        {{ $responsavel->name }} - {{ $rotulosPerfis[$responsavel->role] ?? ucfirst($responsavel->role) }}{{ !$responsavel->is_active ? ' — Inativo' : '' }}
                                    </option>
                                @endforeach
                            </select>

                            <button type="button" id="editar-responsavel-trigger" data-custom-select-trigger class="hidden h-11 w-full min-w-0 items-center justify-between gap-3 rounded-xl border bg-white px-3 text-left text-sm text-stone-900 outline-none transition {{ $errors->has('responsavel_id') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" aria-haspopup="listbox" aria-expanded="false" aria-controls="editar-responsavel-options" aria-labelledby="editar-responsavel-label editar-responsavel-value" aria-describedby="editar-responsavel-required-error @error('responsavel_id') editar-responsavel-error @enderror" @error('responsavel_id') aria-invalid="true" @enderror>
                                <span id="editar-responsavel-value" data-custom-select-value class="truncate">
                                    @if($responsavelAtualModel)
                                        {{ $responsavelAtualModel->name }} - {{ $rotulosPerfis[$responsavelAtualModel->role] ?? ucfirst($responsavelAtualModel->role) }}{{ !$responsavelAtualModel->is_active ? ' — Inativo' : '' }}
                                    @else
                                        Selecione um responsável
                                    @endif
                                </span>
                                <i data-lucide="chevron-down" data-custom-select-chevron class="size-4 shrink-0 text-stone-400 transition-transform" aria-hidden="true"></i>
                            </button>

                            <div data-custom-select-options class="absolute left-0 right-0 top-full z-30 mt-2 hidden overflow-hidden rounded-xl border border-brand-100 bg-white shadow-[0_18px_45px_-18px_rgba(86,20,5,0.35)]">
                                <div class="border-b border-stone-100 p-2">
                                    <div class="relative form-floating form-floating-search">
                                        <label for="editar-responsavel-search" class="form-floating-label text-sm font-bold text-stone-700">Pesquisar responsável</label>
                                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                                        <input type="search" id="editar-responsavel-search" data-custom-select-search class="form-floating-control h-10 w-full rounded-lg border border-stone-300 bg-white pl-9 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20" placeholder="Pesquisar por nome ou perfil" autocomplete="off" spellcheck="false" enterkeyhint="search" aria-controls="editar-responsavel-options">
                                    </div>
                                </div>
                                <div id="editar-responsavel-options" class="max-h-56 overflow-y-auto p-1.5" role="listbox" aria-labelledby="editar-responsavel-label">
                                    <button type="button" data-custom-select-option data-value="" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-stone-700 outline-none transition hover:bg-brand-50 hover:text-brand-800 focus:bg-brand-50 focus:text-brand-800" role="option" aria-selected="{{ $responsavelAtual === '' ? 'true' : 'false' }}" tabindex="-1">
                                        <span>Selecione um responsável</span>
                                        <i data-lucide="check" data-custom-select-check class="size-4 shrink-0 text-brand-600 {{ $responsavelAtual === '' ? '' : 'hidden' }}" aria-hidden="true"></i>
                                    </button>
                                    @foreach($responsaveis as $responsavel)
                                        <button type="button" data-custom-select-option data-value="{{ $responsavel->id }}" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-stone-700 outline-none transition hover:bg-brand-50 hover:text-brand-800 focus:bg-brand-50 focus:text-brand-800" role="option" aria-selected="{{ $responsavelAtual === (string) $responsavel->id ? 'true' : 'false' }}" tabindex="-1">
                                            <span>{{ $responsavel->name }} - {{ $rotulosPerfis[$responsavel->role] ?? ucfirst($responsavel->role) }}{{ !$responsavel->is_active ? ' — Inativo' : '' }}</span>
                                            <i data-lucide="check" data-custom-select-check class="size-4 shrink-0 text-brand-600 {{ $responsavelAtual === (string) $responsavel->id ? '' : 'hidden' }}" aria-hidden="true"></i>
                                        </button>
                                    @endforeach
                                </div>
                                <p id="editar-responsavel-empty" data-custom-select-empty class="hidden px-3 py-5 text-center text-sm font-medium text-stone-500" role="status" aria-live="polite" aria-hidden="true">
                                    Nenhum responsável encontrado.
                                </p>
                            </div>
                        </div>
                        <p id="editar-responsavel-required-error" data-custom-select-required-error class="mt-2 hidden items-center gap-1.5 text-xs font-semibold text-red-600" role="alert">
                            <i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>
                            Selecione o responsável pela ação.
                        </p>
                        @error('responsavel_id')
                            <p id="editar-responsavel-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600">
                                <i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="rounded-xl border border-brand-100 bg-brand-50/70 p-4">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 shadow-sm ring-1 ring-brand-100">
                                <i data-lucide="info" class="size-4.5" aria-hidden="true"></i>
                            </span>
                            <div>
                                <p class="text-sm font-extrabold text-brand-900">Prioridade calculada: {{ $rotuloPrioridade }}</p>
                                <p class="mt-1 text-xs leading-5 text-brand-900/75">
                                    A prioridade será recalculada automaticamente ao salvar as alterações.
                                </p>
                                @if($agendamento->justificativa_prioridade)
                                    <p class="mt-3 border-t border-brand-200 pt-3 text-xs leading-5 text-brand-900/80">
                                        <strong>Justificativa:</strong> {{ $agendamento->justificativa_prioridade }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 py-6 sm:px-7" aria-labelledby="edicao-detalhes-title">
                <x-form.section-heading id="edicao-detalhes-title" icon="file-text" title="Detalhes adicionais" description="Local da ação e informações importantes para o atendimento." />

                <div class="grid gap-5">
                    <x-form.input name="local" label="Local" icon="map-pin" :value="$agendamento->local" maxlength="255" placeholder="Ex: igreja, residência do beneficiário, sala de atendimento" />
                    <x-form.textarea name="notas" label="Observações" icon="file-text" :value="$agendamento->notas" maxlength="5000" placeholder="Descreva informações importantes para o atendimento" />
                </div>
            </section>

            <x-form.actions :cancel-url="route('agendamento.index')" submit-label="Salvar alterações" class="rounded-b-2xl" />
        </form>
    </div>
@endsection
