<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Support\LoginRateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Login extends Controller
{
    public function verificaLogin()
    {

        return view('Login.login');

    }

    public function autenticar(LoginRequest $request, LoginRateLimiter $rateLimiter): RedirectResponse
    {
        $credenciais = $request->validated();

        if (Auth::attempt([...$credenciais, 'is_active' => true])) {
            $rateLimiter->clear($request);
            $request->session()->regenerate();

            $usuario = $request->user();

            if (in_array($usuario->role, ['lider', 'secretaria', 'voluntario'], true)) {
                return redirect()->route('agendamento.index');
            }

            return redirect()->route('home');
        }

        $rateLimiter->recordFailure($request);

        return back()
            ->withErrors(['email' => 'E-mail ou senha inválidos.'])
            ->onlyInput('email');
    }

    public function sair(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
