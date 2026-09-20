@extends('layout.dashboard')

@section('title', 'Chatbot')

@section('content')
    <div class="mx-auto max-w-6xl" data-chatbot>
        <x-ui.page-header
            title="Chatbot"
            description="Esclareça dúvidas sobre o uso e as funcionalidades do sistema."
            :breadcrumbs="[['label' => 'Chatbot']]"
        />

        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-brand-200 bg-brand-50/80 p-4 text-sm text-brand-950" role="note">
            <i data-lucide="shield-check" class="mt-0.5 size-5 shrink-0 text-brand-700" aria-hidden="true"></i>
            <div>
                <p class="font-extrabold">Proteção dos dados dos beneficiários</p>
                <p class="mt-1 leading-6 text-brand-900/80">
                    Este assistente não consulta cadastros. Não informe CPF, telefone, endereço, dados de saúde ou outras informações pessoais.
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" aria-labelledby="chatbot-conversation-title">
                <div class="flex items-center gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <i data-lucide="bot" class="size-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 id="chatbot-conversation-title" class="font-extrabold text-stone-900">Chatbot</h2>
                        <p class="text-xs text-stone-500">Suporte sobre o funcionamento da aplicação</p>
                    </div>
                </div>

                <div
                    class="max-h-[32rem] min-h-80 space-y-5 overflow-y-auto bg-stone-50/60 p-5 sm:p-6"
                    data-chatbot-messages
                    role="log"
                    aria-live="polite"
                    aria-relevant="additions"
                    aria-label="Conversa com o chatbot"
                >
                    <article class="flex items-start gap-3">
                        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[0.65rem] font-extrabold text-brand-800" aria-hidden="true">IA</span>
                        <div class="max-w-[85%] rounded-2xl rounded-tl-sm border border-brand-100 bg-brand-50/70 px-4 py-3 text-stone-800 sm:max-w-[75%]">
                            <p class="text-xs font-extrabold text-brand-800">Chatbot</p>
                            <p class="mt-1 text-sm leading-6 text-stone-700">
                                Olá! Posso orientar você sobre beneficiários, agendamentos, prioridade, relatórios, usuários e permissões. Como posso ajudar?
                            </p>
                        </div>
                    </article>

                    <div
                        class="hidden items-center gap-3 text-sm font-semibold text-stone-500"
                        data-chatbot-loading
                        role="status"
                        aria-live="polite"
                        aria-hidden="true"
                    >
                        <i data-lucide="loader-circle" class="size-5 animate-spin text-brand-600" aria-hidden="true"></i>
                        Processando sua pergunta…
                    </div>
                </div>

                <div class="border-t border-stone-100 p-5 sm:p-6">
                    <div
                        class="mb-4 hidden items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-800"
                        data-chatbot-error
                        role="alert"
                        tabindex="-1"
                    >
                        <i data-lucide="circle-alert" class="mt-0.5 size-4 shrink-0" aria-hidden="true"></i>
                        <span data-chatbot-error-message>Não foi possível enviar sua pergunta.</span>
                    </div>

                    <form method="POST" action="{{ route('chatbot.message') }}" data-chatbot-form class="form-floating" style="--floating-label-left: 1rem; --floating-label-offset: 2.75rem">
                        @csrf

                        <div class="flex items-center justify-between gap-3">
                            <label for="chatbot-message" class="form-floating-label text-sm font-extrabold text-stone-700">Digite sua pergunta</label>
                            <span class="text-xs font-semibold text-stone-400" data-chatbot-character-count aria-live="polite">0/800</span>
                        </div>

                        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="min-w-0 flex-1">
                                <textarea
                                    id="chatbot-message"
                                    name="message"
                                    rows="3"
                                    minlength="3"
                                    maxlength="800"
                                    required
                                    data-chatbot-input
                                    aria-describedby="chatbot-message-help"
                                    class="form-floating-control w-full resize-y rounded-xl border border-stone-300 bg-white px-4 py-3 text-sm leading-6 text-stone-900 outline-none transition placeholder:text-stone-400 hover:border-brand-300 focus:border-brand-600 focus:ring-4 focus:ring-brand-400/20 disabled:cursor-wait disabled:bg-stone-100"
                                    placeholder="Ex.: Como criar um agendamento?"
                                ></textarea>
                                <p id="chatbot-message-help" class="mt-1.5 text-xs leading-5 text-stone-500">
                                    Não inclua dados pessoais. Use Ctrl+Enter para enviar pelo teclado.
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 px-5 text-sm font-bold text-white shadow-[0_10px_24px_-12px_rgba(240,64,0,0.9)] transition hover:from-brand-800 hover:via-brand-700 hover:to-brand-600 focus:outline-none focus:ring-4 focus:ring-brand-400/30 disabled:cursor-wait disabled:opacity-60"
                                data-chatbot-submit
                            >
                                <span class="inline-flex items-center gap-2" data-chatbot-submit-label>
                                    <i data-lucide="send" class="size-4" aria-hidden="true"></i>
                                    Enviar
                                </span>
                                <span class="hidden items-center gap-2" data-chatbot-submit-loading>
                                    <i data-lucide="loader-circle" class="size-4 animate-spin" aria-hidden="true"></i>
                                    Processando
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <aside class="h-fit rounded-2xl border border-stone-200 bg-white p-5 shadow-sm" aria-labelledby="chatbot-suggestions-title">
                <h2 id="chatbot-suggestions-title" class="font-extrabold text-stone-900">Sugestões de perguntas</h2>
                <p class="mt-1 text-xs leading-5 text-stone-500">Selecione um tema para preencher o campo de mensagem.</p>

                <div class="mt-4 grid gap-2" role="group" aria-label="Perguntas sugeridas">
                    @foreach([
                        'Beneficiários' => 'Como cadastrar um beneficiário?',
                        'Agendamentos' => 'Como criar um agendamento?',
                        'Histórico e atendimentos' => 'Como registrar o relatório de um atendimento?',
                        'Prioridade' => 'Como funciona a prioridade?',
                        'Relatórios' => 'Quem pode gerar relatórios?',
                        'Usuários e permissões' => 'Quem pode cadastrar usuários?',
                    ] as $label => $question)
                        <button
                            type="button"
                            class="rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-3 text-left text-sm font-bold text-stone-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/20 disabled:cursor-wait disabled:opacity-60"
                            data-chatbot-suggestion="{{ $question }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
@endsection
