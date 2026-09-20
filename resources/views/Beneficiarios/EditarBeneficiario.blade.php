@extends('layout.dashboard')

@section('title', 'Editar Beneficiário')

@section('content')
    @php
        $possuiSaudeAtual = (string) old('possui_problema_saude', (int) $beneficiario->possui_problema_saude);
        $possuiDeficienciaAtual = (string) old('possui_deficiencia', (int) $beneficiario->possui_deficiencia);
    @endphp

    <div class="mx-auto max-w-5xl">
        <x-ui.page-header title="Editar beneficiário" description="Atualize os dados cadastrais e as informações usadas nos atendimentos sociais." :breadcrumbs="[['label' => 'Beneficiários', 'url' => route('home')], ['label' => $beneficiario->nome_beneficiario, 'url' => route('beneficiarios.show', $beneficiario), 'truncate' => true], ['label' => 'Editar']]" :back-url="route('beneficiarios.show', $beneficiario)" back-label="Voltar à ficha">
            <x-slot:badge>
                <x-ui.badge type="situation" :value="$beneficiario->is_active" :label="$beneficiario->is_active ? 'Ativo' : 'Arquivado'" dot />
            </x-slot:badge>
        </x-ui.page-header>

        <x-ui.form-errors title="Verifique os campos abaixo:" class="mb-6" />

        <form method="POST" action="{{ route('beneficiarios.update', $beneficiario) }}" class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-3 border-b border-brand-100 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:px-7">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm">
                    <i data-lucide="pencil" class="size-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 class="text-lg font-extrabold text-stone-900">Dados do beneficiário</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Os campos marcados com * são obrigatórios.</p>
                </div>
            </div>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="identificacao-title">
                <x-form.section-heading id="identificacao-title" icon="user-round" title="Identificação" description="Dados básicos que identificam o beneficiário." />

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-form.input name="nome_beneficiario" label="Nome do beneficiário" icon="user-round" :value="$beneficiario->nome_beneficiario" maxlength="255" autocomplete="name" placeholder="Nome completo" required />
                    </div>

                    <x-form.input name="cpf" label="CPF" icon="contact-round" :value="$beneficiario->cpf" placeholder="000.000.000-00" maxlength="14" inputmode="numeric" autocomplete="off" required />

                    <x-form.input name="data_nascimento" label="Data de nascimento" icon="cake" :value="$beneficiario->data_nascimento?->format('d/m/Y')" help="Exemplo: 15/07/1985" data-mascara="data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" autocomplete="bday" required />

                    <div class="md:col-span-2">
                        <x-form.calculated-age :value="$beneficiario->idade" constrained />
                    </div>
                </div>
            </section>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="contato-title">
                <x-form.section-heading id="contato-title" icon="phone" title="Contato e endereço" description="Dados utilizados para comunicação e localização." />

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="telefone" label="Telefone" icon="phone" :value="$beneficiario->telefone" placeholder="+55 (47) 99999-9999" maxlength="19" inputmode="tel" autocomplete="tel" required />

                    <x-form.input name="email" label="E-mail" type="email" icon="mail" :value="$beneficiario->email" maxlength="255" autocomplete="email" placeholder="nome@exemplo.com" required />

                    <div class="md:col-span-2" data-cep-consulta data-cep-url="{{ route('beneficiarios.cep.consultar') }}">
                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <x-form.input name="cep" label="CEP" icon="map-pin" :value="$beneficiario->cep" inputmode="numeric" autocomplete="postal-code" maxlength="9" placeholder="00000-000" help="Altere o CEP ou use o botão para consultar novamente. O endereço atual só será substituído após uma consulta bem-sucedida." data-cep-input required />

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
                        <x-form.input name="endereco" label="Endereço" icon="map-pin" :value="$beneficiario->endereco" maxlength="255" autocomplete="street-address" placeholder="Rua, número, bairro e cidade" data-cep-endereco required />
                    </div>
                </div>
            </section>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="saude-title">
                <x-form.section-heading id="saude-title" icon="heart-pulse" title="Saúde e acessibilidade" description="Informações consideradas na organização dos atendimentos." />

                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="rounded-2xl border border-stone-200 bg-stone-50/60 p-4 sm:p-5">
                        <div class="mb-4 flex items-start gap-3">
                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 shadow-sm ring-1 ring-stone-200"><i data-lucide="heart-pulse" class="size-4.5" aria-hidden="true"></i></span>
                            <div><p class="text-sm font-extrabold text-stone-800">Condição de saúde</p><p class="mt-0.5 text-xs leading-5 text-stone-500">Informe doenças ou problemas que precisem de atenção.</p></div>
                        </div>

                        <x-form.custom-select
                            name="possui_problema_saude"
                            label="Possui problema de saúde ou doença?"
                            :options="['' => 'Selecione', '0' => 'Não', '1' => 'Sim']"
                            :selected="$possuiSaudeAtual"
                            required
                            required-message="Informe se o beneficiário possui problema de saúde."
                            data-conditional-select="descricao_problema_saude"
                        />

                        <div class="mt-4 {{ $possuiSaudeAtual === '1' ? '' : 'hidden' }}" data-conditional-field>
                            <x-form.textarea name="descricao_problema_saude" label="Descrição do problema de saúde" :value="$beneficiario->descricao_problema_saude" rows="3" maxlength="2000" placeholder="Descreva a condição de saúde" compact :required="$possuiSaudeAtual === '1'" :disabled="$possuiSaudeAtual !== '1'" />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-stone-200 bg-stone-50/60 p-4 sm:p-5">
                        <div class="mb-4 flex items-start gap-3">
                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 shadow-sm ring-1 ring-stone-200"><i data-lucide="accessibility" class="size-4.5" aria-hidden="true"></i></span>
                            <div><p class="text-sm font-extrabold text-stone-800">Acessibilidade</p><p class="mt-0.5 text-xs leading-5 text-stone-500">Registre necessidades que devam ser consideradas.</p></div>
                        </div>

                        <x-form.custom-select
                            name="possui_deficiencia"
                            label="Possui deficiência?"
                            :options="['' => 'Selecione', '0' => 'Não', '1' => 'Sim']"
                            :selected="$possuiDeficienciaAtual"
                            required
                            required-message="Informe se o beneficiário possui deficiência."
                            data-conditional-select="descricao_deficiencia"
                        />

                        <div class="mt-4 {{ $possuiDeficienciaAtual === '1' ? '' : 'hidden' }}" data-conditional-field>
                            <x-form.textarea name="descricao_deficiencia" label="Descrição da deficiência" :value="$beneficiario->descricao_deficiencia" rows="3" maxlength="2000" placeholder="Descreva a deficiência" compact :required="$possuiDeficienciaAtual === '1'" :disabled="$possuiDeficienciaAtual !== '1'" />
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 py-6 sm:px-7" aria-labelledby="observacoes-title">
                <x-form.section-heading id="observacoes-title" icon="file-text" title="Observações" description="Informações complementares úteis para a equipe." />
                <x-form.textarea name="observacoes" label="Informações adicionais" icon="file-text" :value="$beneficiario->observacoes" rows="4" maxlength="5000" placeholder="Registre qualquer informação importante para futuros atendimentos" />
            </section>

            <x-form.actions :cancel-url="route('beneficiarios.show', $beneficiario)" submit-label="Salvar alterações" />
        </form>

        <section class="mt-6 rounded-2xl border bg-white p-5 shadow-sm sm:p-6 {{ $beneficiario->is_active ? 'border-red-200' : 'border-emerald-200' }}" aria-labelledby="status-cadastro-title">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl {{ $beneficiario->is_active ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                        <i data-lucide="{{ $beneficiario->is_active ? 'archive-restore' : 'rotate-ccw' }}" class="size-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 id="status-cadastro-title" class="font-extrabold text-stone-900">{{ $beneficiario->is_active ? 'Arquivar cadastro' : 'Reativar cadastro' }}</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-stone-500">
                            {{ $beneficiario->is_active
                                ? 'O cadastro deixará de aparecer entre os beneficiários ativos, mas os históricos e agendamentos serão preservados.'
                                : 'O beneficiário voltará a aparecer nas listagens ativas e poderá receber novos agendamentos.' }}
                        </p>
                    </div>
                </div>

                @if($beneficiario->is_active)
                    <form action="{{ route('beneficiarios.inativar', $beneficiario) }}" method="POST" class="shrink-0" onsubmit="return confirm('Deseja arquivar este beneficiário? Os históricos e agendamentos serão preservados e o cadastro poderá ser reativado depois.')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 text-sm font-bold text-red-700 transition hover:border-red-300 hover:bg-red-100 focus:outline-none focus:ring-4 focus:ring-red-300/30 sm:w-auto"><i data-lucide="archive-restore" class="size-4" aria-hidden="true"></i>Arquivar beneficiário</button>
                    </form>
                @else
                    <form action="{{ route('beneficiarios.reativar', $beneficiario) }}" method="POST" class="shrink-0">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-300/40 sm:w-auto"><i data-lucide="rotate-ccw" class="size-4" aria-hidden="true"></i>Reativar beneficiário</button>
                    </form>
                @endif
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/beneficiarios/mascaras.js') }}"></script>
    <script src="{{ asset('js/beneficiarios/viacep.js') }}"></script>
    <script src="{{ asset('js/beneficiarios/CalcularIdade.js') }}"></script>
@endpush
