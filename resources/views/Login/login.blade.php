<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f04000">
    <title>Entrar | {{ config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" sizes="16x16 32x32 48x48" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-brand-50 font-sans text-stone-900 antialiased selection:bg-brand-yellow selection:text-brand-950">
    <main class="login-layout grid min-h-dvh grid-cols-1 grid-rows-[auto_1fr] lg:grid-cols-2 lg:grid-rows-1">
        <section class="login-panel relative flex min-w-0 items-center overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-brand-400 px-5 sm:px-10 lg:px-12 xl:px-20" aria-labelledby="application-name">
            <div class="pointer-events-none absolute -left-24 -top-24 size-72 rounded-full border border-brand-yellow/30"></div>
            <div class="pointer-events-none absolute -bottom-44 -right-32 size-[28rem] rounded-full bg-brand-yellow/15"></div>
            <div class="pointer-events-none absolute bottom-24 right-16 hidden size-20 rounded-full border border-white/30 xl:block"></div>

            <div class="login-brand relative mx-auto flex w-full min-w-0 max-w-md items-center gap-4 sm:gap-6 lg:max-w-xl lg:flex-col lg:items-start">
                <div class="login-logo max-w-full shrink-0 rounded-2xl bg-white/95 p-2 shadow-[0_24px_70px_-30px_rgba(86,20,5,0.55)] ring-1 ring-white/50 backdrop-blur-sm sm:p-3 lg:rounded-[2rem] lg:p-6">
                    <img
                        src="{{ asset('images/logo-mercado-solidario.jpeg') }}"
                        alt="Logotipo do Mercado Solidário"
                        class="h-auto w-full object-contain mix-blend-multiply"
                    >
                </div>

                <div class="min-w-0">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-brand-yellow sm:tracking-[0.28em]">Bem-vindo ao</p>
                    <h1 id="application-name" class="text-base leading-snug font-extrabold wrap-break-word tracking-tight text-white sm:text-xl lg:text-3xl xl:text-4xl">
                        {{ config('app.name') }}
                    </h1>
                </div>
            </div>
        </section>

        <section class="login-panel flex min-w-0 items-center justify-center bg-gradient-to-br from-white via-white to-brand-50 px-5 sm:px-10 lg:px-12 xl:px-16" aria-labelledby="login-title">
            <div class="w-full min-w-0 max-w-md">
                <div class="login-heading">
                    <p class="mb-2 text-sm font-semibold text-brand-700">Área de acesso</p>
                    <h2 id="login-title" class="text-2xl font-extrabold tracking-tight text-stone-900 sm:text-3xl xl:text-4xl">Entre na sua conta</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-500 sm:mt-3 sm:text-base">Informe suas credenciais para acessar o sistema.</p>
                </div>

                <x-ui.toasts />

                @if($errors->any())
                    <x-ui.alert type="error" toast title="Não foi possível entrar" :message="$errors->first()" class="mb-6" />
                @endif

                <form action="{{ route('login.autenticar') }}" method="POST" class="login-form grid min-w-0">
                    @csrf

                    <md-filled-text-field
                        class="login-material-field"
                        type="email"
                        id="email"
                        name="email"
                        label="E-mail"
                        value="{{ old('email') }}"
                        supporting-text="seuemail@exemplo.com"
                        maxlength="255"
                        autocomplete="email"
                        required
                        autofocus
                        @error('email') error error-text="{{ $message }}" @enderror
                    >
                        <i data-lucide="mail" slot="leading-icon" class="size-5" aria-hidden="true"></i>
                    </md-filled-text-field>

                    <md-filled-text-field
                        class="login-material-field"
                        type="password"
                        id="password"
                        name="password"
                        label="Senha"
                        supporting-text="Digite sua senha"
                        maxlength="255"
                        autocomplete="current-password"
                        required
                        @error('password') error error-text="{{ $message }}" @enderror
                    >
                        <i data-lucide="lock-keyhole" slot="leading-icon" class="size-5" aria-hidden="true"></i>
                        <button
                            type="button"
                            slot="trailing-icon"
                            data-password-field-toggle="password"
                            class="inline-flex size-10 items-center justify-center rounded-full text-stone-500 transition hover:bg-brand-100 hover:text-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-400/20"
                            aria-label="Mostrar senha"
                            data-password-show-label="Mostrar senha"
                            data-password-hide-label="Ocultar senha"
                            aria-pressed="false"
                        >
                            <span data-password-show-icon aria-hidden="true"><i data-lucide="eye" class="size-5"></i></span>
                            <span data-password-hide-icon class="hidden" aria-hidden="true"><i data-lucide="eye-off" class="size-5"></i></span>
                        </button>
                    </md-filled-text-field>

                    <div class="text-right">
                        <a href="{{ route('password.request') }}" class="inline-flex rounded-lg text-sm font-bold text-brand-700 underline decoration-brand-300 underline-offset-4 transition hover:text-brand-900 focus:outline-none focus:ring-4 focus:ring-brand-400/20">
                            Esqueceu sua senha?
                        </a>
                    </div>

                    <button type="submit" class="group flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 px-5 text-sm font-bold text-white shadow-[0_12px_28px_-12px_rgba(240,64,0,0.8)] transition hover:from-brand-800 hover:via-brand-700 hover:to-brand-600 focus:outline-none focus:ring-4 focus:ring-brand-400/30 active:translate-y-px">
                        Entrar
                        <i data-lucide="log-in" class="size-5 transition-transform group-hover:translate-x-0.5" aria-hidden="true"></i>
                    </button>
                </form>

            </div>
        </section>
    </main>
</body>
</html>
