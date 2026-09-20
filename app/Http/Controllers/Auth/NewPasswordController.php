<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RedefinirSenhaRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    private const INVALID_LINK = 'O link de recuperação é inválido ou expirou. Solicite um novo link.';

    public function create(Request $request, string $token): View
    {
        return view('Login.redefinir-senha', [
            'token' => $token,
            'email' => is_string($request->query('email'))
                ? mb_strtolower(trim($request->query('email')))
                : '',
        ]);
    }

    public function store(RedefinirSenhaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $status = Password::reset(
            [
                ...$data,
                'is_active' => true,
            ],
            function (User $user, string $password): void {
                if (Hash::check($password, $user->password)) {
                    throw ValidationException::withMessages([
                        'password' => 'A nova senha deve ser diferente da senha anterior.',
                    ]);
                }

                DB::transaction(function () use ($user, $password): void {
                    $user->password = $password;
                    $user->setRememberToken(Str::random(60));
                    $user->save();

                    if (config('session.driver') === 'database') {
                        DB::table(config('session.table', 'sessions'))
                            ->where('user_id', $user->getKey())
                            ->delete();
                    }

                    event(new PasswordReset($user));
                });
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('success', 'Senha redefinida com sucesso. Você já pode acessar a aplicação com sua nova senha.');
        }

        return back()
            ->withErrors(['email' => self::INVALID_LINK])
            ->onlyInput('email');
    }
}
