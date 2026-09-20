@extends('layout.dashboard')

@section('title', 'Novo Agendamento')

@section('content')
    @php
        $beneficiarioAtual = (string) old('beneficiario_id', $beneficiarioSelecionado ?? '');
        $responsavelAtual = (string) old('responsavel_id', '');
        $beneficiarioAtualModel = $beneficiarios->first(
            fn ($beneficiario) => (string) $beneficiario->id === $beneficiarioAtual
        );
        $responsavelAtualModel = $responsaveis->first(
            fn ($responsavel) => (string) $responsavel->id === $responsavelAtual
        );
    @endphp

    <div class="mx-auto max-w-5xl">
        <x-ui.page-header title="Novo agendamento" description="Organize uma nova ação social e defina quando e onde o atendimento acontecerá." :breadcrumbs="[['label' => 'Agenda'], ['label' => 'Novo agendamento']]" :back-url="route('agendamento.index')" back-label="Voltar à agenda" />

        <x-ui.form-errors title="Verifique os campos abaixo:" class="mb-6" />

        <form method="POST" action="{{ route('agendamento.store') }}" class="rounded-2xl border border-stone-200 bg-white shadow-sm">
            @csrf

            <div class="flex items-center gap-3 rounded-t-2xl border-b border-brand-100 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:px-7">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm">
                    <i data-lucide="calendar-plus" class="size-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 class="text-lg font-extrabold text-stone-900">Informações do agendamento</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Os campos marcados com * são obrigatórios.</p>
                </div>
            </div>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="atendimento-title">
                <x-form.section-heading id="atendimento-title" icon="users" title="Atendimento" description="Identifique o beneficiário e a ação que será realizada." />

                <div class="grid gap-5">
                    <div>
                        <label for="beneficiario_id" id="beneficiario-label" class="mb-2 block text-sm font-bold text-stone-700">
                            Beneficiário <span class="text-brand-700" aria-hidden="true">*</span>
                        </label>
                        <div class="relative" data-custom-select>
                            <select name="beneficiario_id" id="beneficiario_id" data-custom-select-native class="h-11 w-full appearance-none rounded-xl border bg-white px-3 pr-9 text-sm text-stone-900 outline-none transition {{ $errors->has('beneficiario_id') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" required @error('beneficiario_id') aria-invalid="true" aria-describedby="beneficiario-error" @enderror>
                                <option value="">Selecione um beneficiário</option>
                                @foreach($beneficiarios as $beneficiario)
                                    <option value="{{ $beneficiario->id }}" @selected($beneficiarioAtual === (string) $beneficiario->id)>
                                        {{ $beneficiario->nome_beneficiario }} - CPF: {{ $beneficiario->cpf }}
                                    </option>
                                @endforeach
                            </select>

                            <button type="button" id="beneficiario-trigger" data-custom-select-trigger class="hidden h-11 w-full min-w-0 items-center justify-between gap-3 rounded-xl border bg-white px-3 text-left text-sm text-stone-900 outline-none transition {{ $errors->has('beneficiario_id') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" aria-haspopup="listbox" aria-expanded="false" aria-controls="beneficiario-options" aria-labelledby="beneficiario-label beneficiario-value" aria-describedby="beneficiario-required-error @error('beneficiario_id') beneficiario-error @enderror" @error('beneficiario_id') aria-invalid="true" @enderror>
                                <span id="beneficiario-value" data-custom-select-value class="truncate">
                                    {{ $beneficiarioAtualModel ? $beneficiarioAtualModel->nome_beneficiario.' - CPF: '.$beneficiarioAtualModel->cpf : 'Selecione um beneficiário' }}
                                </span>
                                <i data-lucide="chevron-down" data-custom-select-chevron class="size-4 shrink-0 text-stone-400 transition-transform" aria-hidden="true"></i>
                            </button>

                            <div data-custom-select-options class="absolute left-0 right-0 top-full z-30 mt-2 hidden overflow-hidden rounded-xl border border-brand-100 bg-white shadow-[0_18px_45px_-18px_rgba(86,20,5,0.35)]">
                                <div class="border-b border-stone-100 p-2">
                                    <div class="relative form-floating form-floating-search">
                                        <label for="beneficiario-search" class="form-floating-label text-sm font-bold text-stone-700">Pesquisar beneficiário</label>
                                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                                        <input type="search" id="beneficiario-search" data-custom-select-search class="form-floating-control h-10 w-full rounded-lg border border-stone-300 bg-white pl-9 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20" placeholder="Pesquisar por nome ou CPF" autocomplete="off" spellcheck="false" enterkeyhint="search" aria-controls="beneficiario-options">
                                    </div>
                                </div>
                                <div id="beneficiario-options" class="max-h-56 overflow-y-auto p-1.5" role="listbox" aria-labelledby="beneficiario-label">
                                    <button type="button" data-custom-select-option data-value="" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-stone-700 outline-none transition hover:bg-brand-50 hover:text-brand-800 focus:bg-brand-50 focus:text-brand-800" role="option" aria-selected="{{ $beneficiarioAtual === '' ? 'true' : 'false' }}" tabindex="-1">
                                        <span>Selecione um beneficiário</span>
                                        <i data-lucide="check" data-custom-select-check class="size-4 shrink-0 text-brand-600 {{ $beneficiarioAtual === '' ? '' : 'hidden' }}" aria-hidden="true"></i>
                                    </button>
                                    @foreach($beneficiarios as $beneficiario)
                                        <button type="button" data-custom-select-option data-value="{{ $beneficiario->id }}" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-stone-700 outline-none transition hover:bg-brand-50 hover:text-brand-800 focus:bg-brand-50 focus:text-brand-800" role="option" aria-selected="{{ $beneficiarioAtual === (string) $beneficiario->id ? 'true' : 'false' }}" tabindex="-1">
                                            <span>{{ $beneficiario->nome_beneficiario }} - CPF: {{ $beneficiario->cpf }}</span>
                                            <i data-lucide="check" data-custom-select-check class="size-4 shrink-0 text-brand-600 {{ $beneficiarioAtual === (string) $beneficiario->id ? '' : 'hidden' }}" aria-hidden="true"></i>
                                        </button>
                                    @endforeach
                                </div>
                                <p id="beneficiario-empty" data-custom-select-empty class="hidden px-3 py-5 text-center text-sm font-medium text-stone-500" role="status" aria-live="polite" aria-hidden="true">
                                    Nenhum beneficiário encontrado.
                                </p>
                            </div>
                        </div>
                        <p id="beneficiario-required-error" data-custom-select-required-error class="mt-2 hidden items-center gap-1.5 text-xs font-semibold text-red-600" role="alert">
                            <i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>
                            Selecione o beneficiário que receberá o atendimento.
                        </p>
                        @error('beneficiario_id')
                            <p id="beneficiario-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600">
                                <i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <x-form.input name="tipo_acao" label="Tipo de ação" icon="clipboard-list" maxlength="100" placeholder="Ex: entrega de cesta básica, atendimento social, visita" required />
                </div>
            </section>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="data-horario-title">
                <x-form.section-heading id="data-horario-title" icon="calendar-days" title="Data e horário" description="Defina quando a ação social será realizada." />

                <div class="grid gap-5 md:grid-cols-3">
                    <x-form.input name="data_agendada" label="Data" icon="calendar-days" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" data-mascara="data" help="Exemplo: 15/07/2026" required />
                    <x-form.input name="hora_agendada" label="Horário inicial" icon="clock" placeholder="hh:mm" maxlength="5" inputmode="numeric" data-mascara="hora" help="Exemplo: 14:30" required />
                    <x-form.input name="hora_final_agendada" label="Horário final" icon="clock" placeholder="hh:mm" maxlength="5" inputmode="numeric" data-mascara="hora" help="Deve ser posterior ao início." />
                </div>
            </section>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="responsabilidade-title">
                <x-form.section-heading id="responsabilidade-title" icon="user-cog" title="Responsabilidade e prioridade" description="Escolha quem acompanhará a ação." />

                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <label for="responsavel_id" id="responsavel-label" class="mb-2 block text-sm font-bold text-stone-700">
                            Responsável pela ação <span class="text-brand-700" aria-hidden="true">*</span>
                        </label>
                        <div class="relative" data-custom-select>
                            <select name="responsavel_id" id="responsavel_id" data-custom-select-native class="h-11 w-full appearance-none rounded-xl border bg-white px-3 pr-9 text-sm text-stone-900 outline-none transition {{ $errors->has('responsavel_id') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" required @error('responsavel_id') aria-invalid="true" aria-describedby="responsavel-error" @enderror>
                                <option value="">Selecione um responsável</option>
                                @foreach($responsaveis as $responsavel)
                                    <option value="{{ $responsavel->id }}" @selected($responsavelAtual === (string) $responsavel->id)>
                                        {{ $responsavel->name }} - {{ $responsavel->role }}
                                    </option>
                                @endforeach
                            </select>

                            <button type="button" id="responsavel-trigger" data-custom-select-trigger class="hidden h-11 w-full min-w-0 items-center justify-between gap-3 rounded-xl border bg-white px-3 text-left text-sm text-stone-900 outline-none transition {{ $errors->has('responsavel_id') ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-400/20' : 'border-stone-300 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20' }}" aria-haspopup="listbox" aria-expanded="false" aria-controls="responsavel-options" aria-labelledby="responsavel-label responsavel-value" aria-describedby="responsavel-required-error @error('responsavel_id') responsavel-error @enderror" @error('responsavel_id') aria-invalid="true" @enderror>
                                <span id="responsavel-value" data-custom-select-value class="truncate">
                                    {{ $responsavelAtualModel ? $responsavelAtualModel->name.' - '.$responsavelAtualModel->role : 'Selecione um responsável' }}
                                </span>
                                <i data-lucide="chevron-down" data-custom-select-chevron class="size-4 shrink-0 text-stone-400 transition-transform" aria-hidden="true"></i>
                            </button>

                            <div data-custom-select-options class="absolute left-0 right-0 top-full z-30 mt-2 hidden overflow-hidden rounded-xl border border-brand-100 bg-white shadow-[0_18px_45px_-18px_rgba(86,20,5,0.35)]">
                                <div class="border-b border-stone-100 p-2">
                                    <div class="relative form-floating form-floating-search">
                                        <label for="responsavel-search" class="form-floating-label text-sm font-bold text-stone-700">Pesquisar responsável</label>
                                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-stone-400" aria-hidden="true"></i>
                                        <input type="search" id="responsavel-search" data-custom-select-search class="form-floating-control h-10 w-full rounded-lg border border-stone-300 bg-white pl-9 pr-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20" placeholder="Pesquisar por nome ou perfil" autocomplete="off" spellcheck="false" enterkeyhint="search" aria-controls="responsavel-options">
                                    </div>
                                </div>
                                <div id="responsavel-options" class="max-h-56 overflow-y-auto p-1.5" role="listbox" aria-labelledby="responsavel-label">
                                    <button type="button" data-custom-select-option data-value="" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-stone-700 outline-none transition hover:bg-brand-50 hover:text-brand-800 focus:bg-brand-50 focus:text-brand-800" role="option" aria-selected="{{ $responsavelAtual === '' ? 'true' : 'false' }}" tabindex="-1">
                                        <span>Selecione um responsável</span>
                                        <i data-lucide="check" data-custom-select-check class="size-4 shrink-0 text-brand-600 {{ $responsavelAtual === '' ? '' : 'hidden' }}" aria-hidden="true"></i>
                                    </button>
                                    @foreach($responsaveis as $responsavel)
                                        <button type="button" data-custom-select-option data-value="{{ $responsavel->id }}" class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-stone-700 outline-none transition hover:bg-brand-50 hover:text-brand-800 focus:bg-brand-50 focus:text-brand-800" role="option" aria-selected="{{ $responsavelAtual === (string) $responsavel->id ? 'true' : 'false' }}" tabindex="-1">
                                            <span>{{ $responsavel->name }} - {{ $responsavel->role }}</span>
                                            <i data-lucide="check" data-custom-select-check class="size-4 shrink-0 text-brand-600 {{ $responsavelAtual === (string) $responsavel->id ? '' : 'hidden' }}" aria-hidden="true"></i>
                                        </button>
                                    @endforeach
                                </div>
                                <p id="responsavel-empty" data-custom-select-empty class="hidden px-3 py-5 text-center text-sm font-medium text-stone-500" role="status" aria-live="polite" aria-hidden="true">
                                    Nenhum responsável encontrado.
                                </p>
                            </div>
                        </div>
                        <p id="responsavel-required-error" data-custom-select-required-error class="mt-2 hidden items-center gap-1.5 text-xs font-semibold text-red-600" role="alert">
                            <i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>
                            Selecione o responsável pela ação.
                        </p>
                        @error('responsavel_id')
                            <p id="responsavel-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600">
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
                                <p class="text-sm font-extrabold text-brand-900">Prioridade automática</p>
                                <p class="mt-1 text-xs leading-5 text-brand-900/75">
                                    O sistema calculará a prioridade com base no histórico familiar: membros, crianças, idosos, deficiência e problemas de saúde.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 py-6 sm:px-7" aria-labelledby="detalhes-title">
                <x-form.section-heading id="detalhes-title" icon="file-text" title="Detalhes adicionais" description="Registre o local e outras informações úteis." />

                <div class="grid gap-5">
                    <x-form.input name="local" label="Local" icon="map-pin" maxlength="255" placeholder="Ex: igreja, residência do beneficiário, sala de atendimento" />
                    <x-form.textarea name="notas" label="Observações" icon="file-text" maxlength="5000" placeholder="Descreva informações importantes para o atendimento" />
                </div>
            </section>

            <x-form.actions :cancel-url="route('agendamento.index')" submit-label="Salvar agendamento" submit-icon="calendar-plus" class="rounded-b-2xl" />
        </form>
    </div>
@endsection
