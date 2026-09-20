<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f04000">
    <title>Redefinir senha | {{ config('app.name') }}</title>
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
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-brand-yellow sm:tracking-[0.28em]">Acesso seguro</p>
                    <h1 id="application-name" class="text-base leading-snug font-extrabold wrap-break-word tracking-tight text-white sm:text-xl lg:text-3xl xl:text-4xl">
                        {{ config('app.name') }}
                    </h1>
                </div>
            </div>
        </section>

        <section class="login-panel flex min-w-0 items-center justify-center bg-gradient-to-br from-white via-white to-brand-50 px-5 sm:px-10 lg:px-12 xl:px-16" aria-labelledby="reset-title">
            <div class="w-full min-w-0 max-w-md">
                <div class="login-heading">
                    <p class="mb-2 text-sm font-semibold text-brand-700">Recuperação de acesso</p>
                    <h2 id="reset-title" class="text-2xl font-extrabold tracking-tight text-stone-900 sm:text-3xl xl:text-4xl">Redefinir senha</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-500 sm:mt-3 sm:text-base">
                        Defina uma senha forte para voltar a acessar o sistema.
                    </p>
                </div>

                <x-ui.toasts />

                @if($errors->any())
                    <x-ui.alert type="error" toast title="Não foi possível redefinir a senha" :message="$errors->first()" class="mb-6" />
                @endif

                <form action="{{ route('password.update') }}" method="POST" class="login-form grid min-w-0">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <md-filled-text-field
                            class="login-material-field"
                            type="email"
                            id="email"
                            name="email"
                            label="E-mail"
                            value="{{ old('email', $email) }}"
                            supporting-text="E-mail que recebeu o link"
                            maxlength="255"
                            autocomplete="email"
                            @error('email') aria-invalid="true" aria-describedby="email-error" error error-text="{{ $message }}" @enderror
                            required
                        >
                            <i data-lucide="mail" slot="leading-icon" class="size-5" aria-hidden="true"></i>
                        </md-filled-text-field>

                        @error('email')
                            <p id="email-error" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-red-600" role="alert">
                                <i data-lucide="circle-alert" class="size-3.5" aria-hidden="true"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <x-form.password
                        name="password"
                        label="Nova senha"
                        minlength="12"
                        maxlength="255"
                        autocomplete="new-password"
                        placeholder="Mínimo de 12 caracteres"
                        described-by="password-requirements"
                        toggle-label="Mostrar nova senha"
                        required
                        autofocus
                    />

                    <x-form.password
                        name="password_confirmation"
                        label="Confirmar nova senha"
                        minlength="12"
                        maxlength="255"
                        autocomplete="new-password"
                        placeholder="Repita a nova senha"
                        described-by="password-requirements"
                        toggle-label="Mostrar confirmação da nova senha"
                        required
                    />

                    <div id="password-requirements" class="rounded-xl border border-brand-100 bg-brand-50/70 p-4">
                        <p class="text-xs leading-5 text-brand-950/80">
                            Use pelo menos 12 caracteres, com letras maiúsculas e minúsculas, número e símbolo. A nova senha deve ser diferente da senha anterior.
                        </p>
                    </div>

                    <button type="submit" class="group flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500 px-5 text-sm font-bold text-white shadow-[0_12px_28px_-12px_rgba(240,64,0,0.8)] transition hover:from-brand-800 hover:via-brand-700 hover:to-brand-600 focus:outline-none focus:ring-4 focus:ring-brand-400/30 active:translate-y-px">
                        Redefinir senha
                        <i data-lucide="lock-keyhole" class="size-5" aria-hidden="true"></i>
                    </button>
                </form>

                <div class="mt-7 text-center">
                    <a href="{{ route('password.request') }}" class="inline-flex items-center gap-2 rounded-lg text-sm font-bold text-brand-700 underline decoration-brand-300 underline-offset-4 transition hover:text-brand-900 focus:outline-none focus:ring-4 focus:ring-brand-400/20">
                        <i data-lucide="rotate-ccw" class="size-4" aria-hidden="true"></i>
                        Solicitar um novo link
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
