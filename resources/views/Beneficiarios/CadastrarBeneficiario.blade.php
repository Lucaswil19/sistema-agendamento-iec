@extends('layout.dashboard')

@section('title', 'Cadastrar Beneficiário')

@section('content')
    @php
        $possuiSaudeAtual = (string) old('possui_problema_saude', '');
        $possuiDeficienciaAtual = (string) old('possui_deficiencia', '');
    @endphp

    <div class="mx-auto max-w-5xl">
        <x-ui.page-header title="Cadastrar beneficiário" description="Registre os dados pessoais e as informações necessárias para os atendimentos sociais." :breadcrumbs="[['label' => 'Beneficiários', 'url' => route('home')], ['label' => 'Novo cadastro']]" :back-url="route('home')" back-label="Voltar aos beneficiários" />

        <x-ui.form-errors title="Verifique os campos abaixo:" class="mb-6" />

        <form method="POST" action="{{ route('beneficiarios.store') }}" class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            @csrf

            <div class="flex items-center gap-3 border-b border-brand-100 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:px-7">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm">
                    <i data-lucide="contact-round" class="size-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 class="text-lg font-extrabold text-stone-900">Ficha do beneficiário</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Os campos marcados com * são obrigatórios.</p>
                </div>
            </div>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="identificacao-title">
                <x-form.section-heading id="identificacao-title" icon="user-round" title="Identificação" description="Informe os dados básicos para identificar o beneficiário." />

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-form.input name="nome_beneficiario" label="Nome do beneficiário" icon="user-round" maxlength="255" autocomplete="name" placeholder="Nome completo" required />
                    </div>

                    <x-form.input name="cpf" label="CPF" icon="contact-round" placeholder="000.000.000-00" maxlength="14" inputmode="numeric" autocomplete="off" required />
                    <x-form.input name="data_nascimento" label="Data de nascimento" icon="cake" data-mascara="data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" autocomplete="bday" help="Exemplo: 15/07/1985" required />

                    <div class="md:col-span-2">
                        <x-form.calculated-age help="Preenchida automaticamente após informar uma data válida." constrained />
                    </div>
                </div>
            </section>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="contato-title">
                <x-form.section-heading id="contato-title" icon="phone" title="Contato e endereço" description="Dados utilizados para comunicação e localização." />

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="telefone" label="Telefone" icon="phone" placeholder="+55 (47) 99999-9999" maxlength="19" inputmode="tel" autocomplete="tel" required />
                    <x-form.input name="email" label="E-mail" type="email" icon="mail" maxlength="255" autocomplete="email" placeholder="nome@exemplo.com" required />

                    <div class="md:col-span-2" data-cep-consulta data-cep-url="{{ route('beneficiarios.cep.consultar') }}">
                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <x-form.input name="cep" label="CEP" icon="map-pin" inputmode="numeric" autocomplete="postal-code" maxlength="9" placeholder="00000-000" help="Informe oito dígitos. A consulta é apenas um auxílio e o endereço permanece editável." data-cep-input required />

                            <div class="flex sm:pt-7">
                                <button type="button" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 text-sm font-bold text-brand-800 transition hover:bg-brand-100 focus:outline-none focus:ring-4 focus:ring-brand-300/30 disabled:cursor-wait disabled:opacity-70 sm:w-auto" data-cep-button aria-controls="cep-consulta-status">
                                    <span data-cep-button-label>Consultar CEP</span>
                                    <span class="hidden items-center gap-2" data-cep-loading>
                                        <i data-lucide="loader-circle" class="size-4 animate-spin" aria-hidden="true"></i>
                                        Consultando...
                                    </span>
                                </button>
                            </div>
                        </div>

                        <p id="cep-consulta-status" class="mt-2 hidden text-sm font-semibold" data-cep-status aria-live="polite" aria-atomic="true"></p>
                    </div>

                    <div class="md:col-span-2">
                        <x-form.input name="endereco" label="Endereço" icon="map-pin" maxlength="255" autocomplete="street-address" placeholder="Rua, número, bairro e cidade" data-cep-endereco required />
                    </div>
                </div>
            </section>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="saude-title">
                <x-form.section-heading id="saude-title" icon="heart-pulse" title="Saúde e acessibilidade" description="Essas informações ajudam a organizar um atendimento adequado." />

                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="rounded-2xl border border-stone-200 bg-stone-50/60 p-4 sm:p-5">
                        <div class="mb-4 flex items-start gap-3">
                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 shadow-sm ring-1 ring-stone-200">
                                <i data-lucide="heart-pulse" class="size-4.5" aria-hidden="true"></i>
                            </span>
                            <div>
                                <p class="text-sm font-extrabold text-stone-800">Condição de saúde</p>
                                <p class="mt-0.5 text-xs leading-5 text-stone-500">Informe doenças ou problemas que precisem de atenção.</p>
                            </div>
                        </div>

                        <x-form.custom-select name="possui_problema_saude" label="Possui problema de saúde ou doença?" :options="['' => 'Selecione', '0' => 'Não', '1' => 'Sim']" :selected="$possuiSaudeAtual" data-conditional-select="descricao_problema_saude" required required-message="Informe se o beneficiário possui problema de saúde." />

                        <div class="mt-4 {{ $possuiSaudeAtual === '1' ? '' : 'hidden' }}" data-conditional-field>
                            <x-form.textarea name="descricao_problema_saude" label="Descrição do problema de saúde" compact maxlength="2000" placeholder="Descreva a condição de saúde" :required="$possuiSaudeAtual === '1'" :disabled="$possuiSaudeAtual !== '1'" />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-stone-200 bg-stone-50/60 p-4 sm:p-5">
                        <div class="mb-4 flex items-start gap-3">
                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 shadow-sm ring-1 ring-stone-200">
                                <i data-lucide="accessibility" class="size-4.5" aria-hidden="true"></i>
                            </span>
                            <div>
                                <p class="text-sm font-extrabold text-stone-800">Acessibilidade</p>
                                <p class="mt-0.5 text-xs leading-5 text-stone-500">Registre necessidades que devam ser consideradas.</p>
                            </div>
                        </div>

                        <x-form.custom-select name="possui_deficiencia" label="Possui deficiência?" :options="['' => 'Selecione', '0' => 'Não', '1' => 'Sim']" :selected="$possuiDeficienciaAtual" data-conditional-select="descricao_deficiencia" required required-message="Informe se o beneficiário possui deficiência." />

                        <div class="mt-4 {{ $possuiDeficienciaAtual === '1' ? '' : 'hidden' }}" data-conditional-field>
                            <x-form.textarea name="descricao_deficiencia" label="Descrição da deficiência" compact maxlength="2000" placeholder="Descreva a deficiência" :required="$possuiDeficienciaAtual === '1'" :disabled="$possuiDeficienciaAtual !== '1'" />
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 py-6 sm:px-7" aria-labelledby="observacoes-title">
                <x-form.section-heading id="observacoes-title" icon="file-text" title="Observações" description="Adicione informações complementares úteis para a equipe." />
                <x-form.textarea name="observacoes" label="Informações adicionais" icon="file-text" maxlength="5000" placeholder="Registre aqui qualquer informação importante para futuros atendimentos" />
            </section>

            <x-form.actions :cancel-url="route('home')" submit-label="Salvar beneficiário" submit-icon="clipboard-plus" />
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/beneficiarios/mascaras.js') }}"></script>
    <script src="{{ asset('js/beneficiarios/viacep.js') }}"></script>
    <script src="{{ asset('js/beneficiarios/CalcularIdade.js') }}"></script>
@endpush
