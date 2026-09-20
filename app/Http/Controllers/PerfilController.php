<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtualizarSenhaPerfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function edit(): View
    {
        return view('Perfil.EditarPerfil');
    }

    public function updatePassword(
        AtualizarSenhaPerfilRequest $request
    ): RedirectResponse {
        $dados = $request->validated();
        $usuario = $request->user();

        DB::transaction(function () use (
            $request,
            $dados,
            $usuario
        ): void {
            $usuario->password = $dados['password'];
            $usuario->remember_token = Str::random(60);
            $usuario->save();
            $this->revogarOutrasSessoes(
                $request,
                $usuario->getKey()
            );
        });

        $request->session()->regenerate();

        return redirect()
            ->route('perfil.edit')
            ->with(
                'success',
                'Senha alterada com sucesso. As outras sessões foram encerradas.'
            );
    }
    private function revogarOutrasSessoes(
        Request $request,
        int|string $userId
    ): void {
        if (
            config('session.driver') !== 'database'
            || ! $request->hasSession()
        ) {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $userId)
            ->where(
                'id',
                '!=',
                $request->session()->getId()
            )
            ->delete();
    }
}