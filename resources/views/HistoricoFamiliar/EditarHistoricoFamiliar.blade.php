@extends('layout.dashboard')

@section('title', 'Editar Histórico Familiar')

@section('content')
    @php
        $possuiSaudeAtual = (string) old('possui_problema_saude', (int) $historicoFamiliar->possui_problema_saude);
        $possuiDeficienciaAtual = (string) old('possui_deficiencia', (int) $historicoFamiliar->possui_deficiencia);
    @endphp

    <div class="mx-auto max-w-5xl">
        <x-ui.page-header title="Editar membro familiar" description="Atualize as informações utilizadas no acompanhamento e no cálculo de prioridade da família." :breadcrumbs="[['label' => 'Beneficiários', 'url' => route('home')], ['label' => $beneficiario->nome_beneficiario, 'url' => route('beneficiarios.show', $beneficiario), 'truncate' => true], ['label' => 'Editar histórico familiar']]" :back-url="route('beneficiarios.show', $beneficiario)" back-label="Voltar à ficha" />

        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <div class="flex min-w-0 items-center gap-4 rounded-2xl border border-brand-100 bg-brand-50/70 p-4 shadow-sm sm:p-5">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm"><i data-lucide="contact-round" class="size-5" aria-hidden="true"></i></span>
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-700">Beneficiário</p>
                    <p class="mt-1 truncate font-extrabold text-stone-900">{{ $beneficiario->nome_beneficiario }}</p>
                    <p class="mt-0.5 text-xs font-semibold text-stone-500">CPF {{ $beneficiario->cpf }}</p>
                </div>
            </div>
            <div class="flex min-w-0 items-center gap-4 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm sm:p-5">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-stone-600"><i data-lucide="user-round" class="size-5" aria-hidden="true"></i></span>
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-stone-400">Membro em edição</p>
                    <p class="mt-1 truncate font-extrabold text-stone-900">{{ $historicoFamiliar->nome }}</p>
                    <p class="mt-0.5 text-xs font-semibold text-stone-500">{{ ucfirst($historicoFamiliar->parentesco) }}</p>
                </div>
            </div>
        </div>

        <x-ui.form-errors title="Verifique os campos abaixo:" class="mb-6" />

        <form method="POST" action="{{ route('historico-familiar.update', [$beneficiario, $historicoFamiliar]) }}" class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-3 border-b border-brand-100 bg-gradient-to-r from-brand-50 to-white px-5 py-5 sm:px-7">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm"><i data-lucide="pencil" class="size-5" aria-hidden="true"></i></span>
                <div><h2 class="text-lg font-extrabold text-stone-900">Informações do membro familiar</h2><p class="mt-0.5 text-sm text-stone-500">Os campos marcados com * são obrigatórios.</p></div>
            </div>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="identificacao-familiar-title">
                <x-form.section-heading id="identificacao-familiar-title" icon="user-round" title="Identificação" description="Dados que identificam o membro e seu vínculo familiar." />

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-form.input name="nome" label="Nome do membro familiar" icon="user-round" :value="$historicoFamiliar->nome" maxlength="255" autocomplete="name" placeholder="Nome completo" required />
                    </div>

                    <x-form.input name="data_nascimento" label="Data de nascimento" icon="cake" :value="$historicoFamiliar->data_nascimento?->format('d/m/Y')" help="Exemplo: 15/07/2010" data-mascara="data" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric" autocomplete="bday" required />

                    <x-form.calculated-age :value="$historicoFamiliar->idade" />

                    <div class="md:col-span-2">
                        <x-form.input name="parentesco" label="Parentesco" icon="users" :value="$historicoFamiliar->parentesco" maxlength="100" placeholder="Ex.: filho, mãe, pai, avó ou irmão" required />
                    </div>
                </div>
            </section>

            <section class="border-b border-stone-100 px-5 py-6 sm:px-7" aria-labelledby="saude-familiar-title">
                <x-form.section-heading id="saude-familiar-title" icon="heart-pulse" title="Saúde e acessibilidade" description="Condições consideradas no acompanhamento familiar." />

                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="rounded-2xl border border-stone-200 bg-stone-50/60 p-4 sm:p-5">
                        <div class="mb-4 flex items-start gap-3">
                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-brand-700 shadow-sm ring-1 ring-stone-200"><i data-lucide="heart-pulse" class="size-4.5" aria-hidden="true"></i></span>
                            <div><p class="text-sm font-extrabold text-stone-800">Condição de saúde</p><p class="mt-0.5 text-xs leading-5 text-stone-500">Informe doenças ou problemas que precisem de atenção.</p></div>
                        </div>
                        <x-form.custom-select
                            name="possui_problema_saude"
                            label="Possui problema de saúde?"
                            :options="['' => 'Selecione', '0' => 'Não', '1' => 'Sim']"
                            :selected="$possuiSaudeAtual"
                            required
                            required-message="Informe se o membro possui problema de saúde."
                            data-conditional-select="descricao_problema_saude"
                        />

                        <div class="mt-4 {{ $possuiSaudeAtual === '1' ? '' : 'hidden' }}" data-conditional-field>
                            <x-form.textarea name="descricao_problema_saude" label="Descrição do problema de saúde" :value="$historicoFamiliar->descricao_problema_saude" rows="3" maxlength="2000" placeholder="Descreva a condição de saúde" compact :required="$possuiSaudeAtual === '1'" :disabled="$possuiSaudeAtual !== '1'" />
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
                            required-message="Informe se o membro possui deficiência."
                            data-conditional-select="descricao_deficiencia"
                        />

                        <div class="mt-4 {{ $possuiDeficienciaAtual === '1' ? '' : 'hidden' }}" data-conditional-field>
                            <x-form.textarea name="descricao_deficiencia" label="Descrição da deficiência" :value="$historicoFamiliar->descricao_deficiencia" rows="3" maxlength="2000" placeholder="Descreva a deficiência" compact :required="$possuiDeficienciaAtual === '1'" :disabled="$possuiDeficienciaAtual !== '1'" />
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 py-6 sm:px-7" aria-labelledby="observacoes-familiar-title">
                <x-form.section-heading id="observacoes-familiar-title" icon="file-text" title="Observações" description="Informações complementares relevantes para a família." />
                <x-form.textarea name="observacoes" label="Observações gerais" icon="file-text" :value="$historicoFamiliar->observacoes" rows="4" maxlength="5000" placeholder="Informe observações importantes sobre o membro familiar" />
            </section>

            <x-form.actions :cancel-url="route('beneficiarios.show', $beneficiario)" submit-label="Salvar alterações" />
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/beneficiarios/CalcularIdade.js') }}"></script>
@endpush
