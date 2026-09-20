<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    private const STATUS_AGENDA_ABERTA = ['agendado', 'reagendado'];

    public function index(UserIndexRequest $request)
    {
        $filtros = $request->validated();

        $usuarios = User::query()
            ->withCount(['agendamentosCriados', 'agendamentosResponsavel', 'historicoAcoes'])
            ->when(! empty($filtros['search']), function ($query) use ($filtros) {
                $search = $filtros['search'];

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(! empty($filtros['role']), function ($query) use ($filtros) {
                $query->where('role', $filtros['role']);
            })
            ->when(($filtros['status'] ?? null) === 'ativo', function ($query) {
                $query->where('is_active', true);
            })
            ->when(($filtros['status'] ?? null) === 'inativo', function ($query) {
                $query->where('is_active', false);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('Usuarios.ListarUsuarios', compact('usuarios'));
    }

    public function create()
    {
        return view('Usuarios.CriarUsuarios');
    }

    public function store(UserRequest $request)
    {
        $dados = $request->validated();
        $user = new User($dados);
        $user->role = $dados['role'];
        $user->is_active = true;
        $user->save();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuário cadastrado com sucesso.');
    }

    public function edit(User $user)
    {
        return view('Usuarios.EditarUsuarios', compact('user'));
    }

    public function update(UserRequest $request, User $user)
    {
        $dados = $request->validated();
        $senhaAlterada = filled($dados['password'] ?? null);
        unset($dados['current_password']);

        if (! $senhaAlterada) {
            unset($dados['password']);
        }

        DB::transaction(function () use ($request, $user, $dados, $senhaAlterada): void {
            $lideresAtivosBloqueados = $this->bloquearLideresAtivos();

            $usuarioBloqueado = User::query()
                ->lockForUpdate()
                ->findOrFail($user->getKey());

            if (
                $usuarioBloqueado->role === 'lider'
                && $dados['role'] !== 'lider'
                && $this->ehUltimoLiderAtivo(
                    $usuarioBloqueado,
                    $lideresAtivosBloqueados
                )
            ) {
                throw ValidationException::withMessages([
                    'role' => 'Não é possível alterar o perfil do último líder ativo do sistema.',
                ]);
            }

            if (
                $dados['role'] === 'secretaria'
                && $usuarioBloqueado->agendamentosResponsavel()
                    ->whereIn('status', self::STATUS_AGENDA_ABERTA)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'role' => 'Reatribua ou encerre os agendamentos abertos antes de alterar este usuário para secretária.',
                ]);
            }

            $usuarioBloqueado->fill($dados);
            $usuarioBloqueado->role = $dados['role'];
            $usuarioBloqueado->save();

            if ($senhaAlterada) {
                $this->revogarSessoesAnteriores($request, $usuarioBloqueado);
            }
        });

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function inativar(Request $request, User $user)
    {
        DB::transaction(function () use ($request, $user): void {
            $lideresAtivosBloqueados = $this->bloquearLideresAtivos();

            $usuarioBloqueado = User::query()
                ->lockForUpdate()
                ->findOrFail($user->getKey());

            if (Auth::id() === $usuarioBloqueado->getKey()) {
                throw ValidationException::withMessages([
                    'usuario' => 'Você não pode inativar o próprio usuário enquanto está conectado.',
                ]);
            }

            if ($this->ehUltimoLiderAtivo(
                $usuarioBloqueado,
                $lideresAtivosBloqueados
            )) {
                throw ValidationException::withMessages([
                    'usuario' => 'Não é possível inativar o último líder ativo do sistema.',
                ]);
            }

            if (
                $usuarioBloqueado->agendamentosResponsavel()
                    ->whereIn('status', self::STATUS_AGENDA_ABERTA)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'usuario' => 'Reatribua ou encerre os agendamentos abertos deste responsável antes de inativá-lo.',
                ]);
            }

            $usuarioBloqueado->is_active = false;
            $usuarioBloqueado->save();

            $this->revogarSessoesAnteriores($request, $usuarioBloqueado);
        });

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuário inativado com sucesso.');
    }

    public function reativar(User $user)
    {
        DB::transaction(function () use ($user): void {
            $usuarioBloqueado = User::query()
                ->lockForUpdate()
                ->findOrFail($user->getKey());
            $usuarioBloqueado->is_active = true;
            $usuarioBloqueado->save();
        });

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuário reativado com sucesso.');
    }

    public function destroy(User $user)
    {
        if (Auth::id() === $user->id) {
            return back()->withErrors([
                'usuario' => 'Você não pode excluir o próprio usuário enquanto está conectado.',
            ]);
        }

        $erro = DB::transaction(function () use ($user): ?string {
            $usuarioBloqueado = User::query()
                ->lockForUpdate()
                ->findOrFail($user->getKey());

            if ($usuarioBloqueado->is_active) {
                return 'Inative o usuário antes de realizar a exclusão permanente.';
            }

            $possuiVinculos = $usuarioBloqueado->agendamentosCriados()->exists()
                || $usuarioBloqueado->agendamentosResponsavel()->exists()
                || $usuarioBloqueado->historicoAcoes()->exists();

            if ($possuiVinculos) {
                return 'Este usuário possui registros vinculados e não pode ser excluído. Mantenha-o inativo.';
            }

            $usuarioBloqueado->delete();

            return null;
        });

        if ($erro !== null) {
            return back()->withErrors([
                'usuario' => $erro,
            ]);
        }

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuário excluído permanentemente com sucesso.');
    }

    private function bloquearLideresAtivos(): Collection
    {
        return User::query()
            ->where('role', 'lider')
            ->where('is_active', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'role', 'is_active']);
    }

    private function ehUltimoLiderAtivo(
        User $user,
        Collection $lideresAtivosBloqueados
    ): bool {
        if ($user->role !== 'lider' || ! $user->is_active) {
            return false;
        }

        return $lideresAtivosBloqueados
            ->where('id', '!=', $user->getKey())
            ->isEmpty();
    }

    private function revogarSessoesAnteriores(Request $request, User $user): void
    {
        $user->remember_token = Str::random(60);
        $user->save();

        if (config('session.driver') !== 'database') {
            return;
        }

        $sessoes = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey());

        if (Auth::id() === $user->getKey() && $request->hasSession()) {
            $sessoes->where('id', '!=', $request->session()->getId());
        }

        $sessoes->delete();
    }
}
