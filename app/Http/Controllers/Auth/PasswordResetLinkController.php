<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SolicitarRecuperacaoSenhaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    private const GENERIC_RESPONSE = 'Caso exista uma conta ativa associada ao e-mail informado, as instruções para redefinição da senha serão enviadas.';

    public function create(): View
    {
        return view('Login.esqueci-senha');
    }

    public function store(SolicitarRecuperacaoSenhaRequest $request): RedirectResponse
    {
        $email = $request->validated('email');

        try {
            Password::sendResetLink([
                'email' => $email,
                'is_active' => true,
            ]);
        } catch (Throwable) {
            Log::warning('Falha ao processar o envio de recuperação de senha.');
        }

        return back()
            ->with('status', self::GENERIC_RESPONSE)
            ->withInput(['email' => $email]);
    }
}
