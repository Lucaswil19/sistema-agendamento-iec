<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f04000">

    <title>@yield('title') | {{ config('app.name') }}</title>

    <link rel="icon" type="image/x-icon" sizes="16x16 32x32 48x48" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-gradient-to-br from-brand-50 via-[#fffaf5] to-brand-100/70 bg-fixed font-sans text-stone-900 antialiased selection:bg-brand-yellow selection:text-brand-950">
    <a
        href="#conteudo-principal"
        class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[9999] focus:rounded-md focus:bg-white focus:px-4 focus:py-3 focus:text-sm focus:font-semibold focus:text-gray-900 focus:shadow-lg focus:ring-2 focus:ring-gray-900"
    >
        Ir para o conteúdo principal
    </a>

    @php
        $usuario = auth()->user();
        $rotuloPerfil = match ($usuario->role) {
            'lider' => 'Líder',
            'secretaria' => 'Secretaria',
            'voluntario' => 'Voluntário',
            default => ucfirst($usuario->role),
        };
        $inicialUsuario = \Illuminate\Support\Str::upper(
            \Illuminate\Support\Str::substr($usuario->name, 0, 1)
        );
        $beneficiariosAtivo = request()->routeIs('home', 'beneficiarios.*');
        $agendaAtiva = request()->routeIs('agendamento.*', 'historico-acoes.*');
        $chatbotAtivo = request()->routeIs('chatbot.*');
        $relatoriosAtivo = request()->routeIs('relatorios.*');
        $usuariosAtivo = request()->routeIs('usuarios.*');
    @endphp

    <header class="fixed inset-x-0 top-0 z-50 h-20 border-b border-stone-200 bg-white/95 shadow-sm backdrop-blur">
        <div class="flex h-full items-center justify-between gap-4 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <button
                    type="button"
                    class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl text-stone-600 transition hover:bg-brand-50 hover:text-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/20 lg:hidden"
                    data-sidebar-toggle
                    aria-controls="dashboard-sidebar"
                    aria-expanded="false"
                    aria-label="Abrir menu principal"
                >
                    <i data-lucide="menu" class="size-6" aria-hidden="true"></i>
                </button>

                <button
                    type="button"
                    class="hidden size-11 shrink-0 items-center justify-center rounded-xl text-stone-500 transition hover:bg-brand-50 hover:text-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/20 lg:inline-flex"
                    data-sidebar-collapse
                    aria-controls="dashboard-sidebar"
                    aria-pressed="false"
                    aria-label="Recolher menu lateral"
                    title="Recolher menu lateral"
                >
                    <span data-sidebar-collapse-icon aria-hidden="true">
                        <i data-lucide="panel-left-close" class="size-5"></i>
                    </span>
                    <span class="hidden" data-sidebar-expand-icon aria-hidden="true">
                        <i data-lucide="panel-left-open" class="size-5"></i>
                    </span>
                </button>

                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3 rounded-xl focus:outline-none focus:ring-4 focus:ring-brand-400/20">
                    <img
                        src="{{ asset('images/logo-iec.jpeg') }}"
                        alt="Logotipo da Igreja Evangélica Calvário"
                        class="size-12 shrink-0 rounded-xl object-cover shadow-sm ring-1 ring-brand-200"
                    >
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-bold uppercase tracking-[0.18em] text-brand-700">Sistema de Agendamento</span>
                        <span class="block truncate text-sm font-extrabold text-stone-900 sm:text-base">Igreja Evangélica Calvário</span>
                    </span>
                </a>
            </div>

        </div>
    </header>

    <div
        class="fixed inset-0 top-20 z-30 hidden bg-stone-950/45 backdrop-blur-[1px] lg:hidden"
        data-sidebar-overlay
        aria-hidden="true"
    ></div>

    <aside
        id="dashboard-sidebar"
        class="fixed inset-y-0 left-0 top-20 z-40 flex w-72 -translate-x-full flex-col border-r border-brand-100 bg-white/95 shadow-xl backdrop-blur-sm transition-[width,transform] duration-300 ease-out lg:w-72 lg:translate-x-0 lg:shadow-none"
        data-sidebar
        aria-label="Menu principal"
    >
        <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 lg:hidden">
            <p class="text-sm font-extrabold text-stone-800">Menu principal</p>
            <button
                type="button"
                class="inline-flex size-9 items-center justify-center rounded-lg text-stone-500 transition hover:bg-stone-100 hover:text-stone-800 focus:outline-none focus:ring-4 focus:ring-brand-400/20"
                data-sidebar-close
                aria-label="Fechar menu principal"
            >
                <i data-lucide="x" class="size-5" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-6 transition-[padding] duration-300" data-sidebar-nav>
            <p class="mb-3 px-3 text-[0.68rem] font-extrabold uppercase tracking-[0.2em] text-stone-400" data-sidebar-label>Navegação</p>
            <ul class="space-y-1.5">
                <x-navigation.sidebar-group id="sidebar-beneficiarios-menu" label="Beneficiários" icon="users" :active="$beneficiariosAtivo">
                    <x-navigation.sidebar-link :href="route('home')" label="Lista de beneficiários" icon="contact-round" :active="request()->routeIs('home', 'beneficiarios.show', 'beneficiarios.edit')" />

                    @if($usuario->role === 'lider')
                        <x-navigation.sidebar-link :href="route('beneficiarios.create')" label="Novo beneficiário" icon="plus" :active="request()->routeIs('beneficiarios.create')" />
                    @endif
                </x-navigation.sidebar-group>

                <x-navigation.sidebar-group id="sidebar-agenda-menu" label="Agenda" icon="calendar-days" :active="$agendaAtiva" :badge="$totalPendenciasVencidas ?? null">
                    <x-navigation.sidebar-link :href="route('agendamento.index')" label="Próximas ações" icon="calendar-days" :active="request()->routeIs('agendamento.index', 'agendamento.show', 'agendamento.edit', 'historico-acoes.*')" />

                    @if($usuario->role === 'lider')
                        <x-navigation.sidebar-link :href="route('agendamento.create')" label="Novo agendamento" icon="calendar-plus" :active="request()->routeIs('agendamento.create')" />
                    @endif

                    <x-navigation.sidebar-link :href="route('agendamento.historico')" label="Histórico e pendências" icon="history" :active="request()->routeIs('agendamento.historico')" />
                </x-navigation.sidebar-group>

                <x-navigation.sidebar-link :href="route('chatbot.index')" label="Chatbot" icon="bot" :active="$chatbotAtivo" />

            </ul>

            @if($usuario->role === 'lider')
                <p class="mb-3 mt-8 px-3 text-[0.68rem] font-extrabold uppercase tracking-[0.2em] text-stone-400" data-sidebar-label>Gestão</p>
                <ul class="space-y-1.5" data-sidebar-management-list>
                    <x-navigation.sidebar-group id="sidebar-relatorios-menu" label="Relatórios" icon="bar-chart-3" :active="$relatoriosAtivo">
                        <x-navigation.sidebar-link :href="route('relatorios.atendimentos')" label="Atendimentos" title="Relatório de atendimentos" icon="bar-chart-3" :active="request()->routeIs('relatorios.atendimentos')" />
                        <x-navigation.sidebar-link :href="route('relatorios.beneficiarios')" label="Beneficiários" title="Relatório de beneficiários" icon="clipboard-list" :active="request()->routeIs('relatorios.beneficiarios')" />
                    </x-navigation.sidebar-group>

                    <x-navigation.sidebar-group id="sidebar-usuarios-menu" label="Usuários" icon="user-cog" :active="$usuariosAtivo">
                        <x-navigation.sidebar-link :href="route('usuarios.index')" label="Lista de usuários" icon="users" :active="request()->routeIs('usuarios.index', 'usuarios.edit')" />
                        <x-navigation.sidebar-link :href="route('usuarios.create')" label="Novo usuário" icon="plus" :active="request()->routeIs('usuarios.create')" />
                    </x-navigation.sidebar-group>
                </ul>
            @endif
        </nav>

        <div class="border-t border-brand-100 p-4 transition-[padding]" data-sidebar-user-footer>
            <div class="flex min-w-0 items-center gap-3 rounded-xl bg-brand-50/70 p-3 ring-1 ring-brand-100 transition-[padding]" data-sidebar-user-card>
                <a
                    href="{{ route('perfil.edit') }}"
                    class="group flex min-w-0 flex-1 items-center gap-3 rounded-lg focus:outline-none focus:ring-4 focus:ring-brand-400/20"
                    data-sidebar-link
                    aria-label="Abrir meu perfil"
                    title="Abrir meu perfil"
                >
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-700 to-brand-400 text-sm font-extrabold text-white shadow-sm ring-brand-300 transition group-hover:ring-4" title="{{ $usuario->name }} — {{ $rotuloPerfil }}">
                        {{ $inicialUsuario }}
                    </span>
                    <span class="min-w-0 flex-1" data-sidebar-label>
                        <span class="block truncate text-sm font-bold text-stone-800 transition group-hover:text-brand-800">{{ $usuario->name }}</span>
                        <span class="block truncate text-xs font-medium text-stone-500">{{ $rotuloPerfil }}</span>
                    </span>
                </a>
                <form action="{{ route('logout') }}" method="POST" class="shrink-0" data-sidebar-label>
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg px-2.5 text-xs font-bold text-stone-500 transition hover:bg-white hover:text-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-400/20"
                        aria-label="Sair do sistema"
                        title="Sair do sistema"
                    >
                        <i data-lucide="log-out" class="size-4" aria-hidden="true"></i>
                        <span>Sair</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="flex min-h-screen flex-col pt-20 transition-[padding] duration-300 lg:pl-72" data-dashboard-content>
        <main id="conteudo-principal" tabindex="-1" class="mx-auto w-full max-w-[100rem] flex-1 px-4 py-6 sm:px-6 sm:py-8 xl:px-10">
            <x-ui.toasts />
            @yield('content')
        </main>

        <footer class="border-t border-brand-100 bg-white/70 px-4 py-4 backdrop-blur-sm sm:px-6 xl:px-10">
            <div class="mx-auto flex w-full max-w-[100rem] items-center justify-center text-center text-xs font-semibold text-stone-500 sm:text-sm">
                <p>&copy; {{ now()->year }} Igreja Evangélica Calvário</p>
            </div>
        </footer>
    </div>

    <script src="{{ asset('js/beneficiarios/mascarasDigitadas.js') }}"></script>
    @stack('scripts')
</body>
</html>
