<?php

use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BeneficiariosController;
use App\Http\Controllers\CepController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\HistoricoAcoesController;
use App\Http\Controllers\HistoricoFamiliarController;
use App\Http\Controllers\Login;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [Login::class, 'verificaLogin'])->name('login');
Route::post('/login', [Login::class, 'autenticar'])
    ->middleware('login.throttle')
    ->name('login.autenticar');
Route::post('/logout', [Login::class, 'sair'])->name('logout');

Route::middleware('guest')->group(function () {
    Route::get('/esqueci-senha', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('/esqueci-senha', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/redefinir-senha/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('/redefinir-senha', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/perfil', [PerfilController::class, 'edit'])
        ->name('perfil.edit');
    Route::put('/perfil/senha',
        [PerfilController::class, 'updatePassword'])->name('perfil.senha.update');

    Route::get('/', [BeneficiariosController::class, 'index'])
        ->name('home')
        ->middleware('role:lider,secretaria,voluntario');

    Route::get('/chatbot', [ChatbotController::class, 'index'])
        ->name('chatbot.index')
        ->middleware('role:lider,secretaria,voluntario');

    Route::post('/chatbot/mensagem', [ChatbotController::class, 'message'])
        ->name('chatbot.message')
        ->middleware([
            'role:lider,secretaria,voluntario',
            'throttle:10,1',
        ]);

    Route::get('/beneficiarios/criar', [BeneficiariosController::class, 'create'])
        ->name('beneficiarios.create')
        ->middleware('role:lider');

    Route::post('/beneficiarios', [BeneficiariosController::class, 'store'])
        ->name('beneficiarios.store')
        ->middleware('role:lider');

    Route::get('/beneficiarios/consultar-cep', [CepController::class, 'consultar'])
        ->name('beneficiarios.cep.consultar')
        ->middleware([
            'role:lider',
            'throttle:30,1',
        ]);

    Route::get('/beneficiarios/{beneficiario}', [BeneficiariosController::class, 'show'])
        ->name('beneficiarios.show')
        ->middleware('role:lider,secretaria,voluntario');

    Route::get('/beneficiarios/{beneficiario}/editar', [BeneficiariosController::class, 'edit'])
        ->name('beneficiarios.edit')
        ->middleware('role:lider');

    Route::put('/beneficiarios/{beneficiario}', [BeneficiariosController::class, 'update'])
        ->name('beneficiarios.update')
        ->middleware('role:lider');

    Route::patch('/beneficiarios/{beneficiario}/inativar', [BeneficiariosController::class, 'inativar'])
        ->name('beneficiarios.inativar')
        ->middleware('role:lider');

    Route::patch('/beneficiarios/{beneficiario}/reativar', [BeneficiariosController::class, 'reativar'])
        ->name('beneficiarios.reativar')
        ->middleware('role:lider');

    Route::get('/beneficiarios/{beneficiario}/historico-familiar/criar', [HistoricoFamiliarController::class, 'create'])
        ->name('historico-familiar.create')
        ->middleware('role:lider');

    Route::post('/beneficiarios/{beneficiario}/historico-familiar', [HistoricoFamiliarController::class, 'store'])
        ->name('historico-familiar.store')
        ->middleware('role:lider');

    Route::get('/beneficiarios/{beneficiario}/historico-familiar/{historicoFamiliar}/editar', [HistoricoFamiliarController::class, 'edit'])
        ->name('historico-familiar.edit')
        ->middleware('role:lider');

    Route::put('/beneficiarios/{beneficiario}/historico-familiar/{historicoFamiliar}', [HistoricoFamiliarController::class, 'update'])
        ->name('historico-familiar.update')
        ->middleware('role:lider');

    Route::delete('/beneficiarios/{beneficiario}/historico-familiar/{historicoFamiliar}', [HistoricoFamiliarController::class, 'destroy'])
        ->name('historico-familiar.destroy')
        ->middleware('role:lider');

    Route::get('/agendamento', [AgendamentoController::class, 'index'])
        ->name('agendamento.index')
        ->middleware('role:lider,secretaria,voluntario');

    Route::get('/agendamento/criar', [AgendamentoController::class, 'create'])
        ->name('agendamento.create')
        ->middleware('role:lider');

    Route::post('/agendamento', [AgendamentoController::class, 'store'])
        ->name('agendamento.store')
        ->middleware('role:lider');

    Route::get(
        '/agendamento/historico',
        [AgendamentoController::class, 'historico'])
        ->name('agendamento.historico')
        ->middleware('role:lider,secretaria,voluntario');

    Route::get('/agendamento/{agendamento}', [AgendamentoController::class, 'show'])
        ->name('agendamento.show')
        ->middleware('role:lider,secretaria,voluntario');

    Route::get('/agendamento/{agendamento}/editar', [AgendamentoController::class, 'edit'])
        ->name('agendamento.edit')
        ->middleware('role:lider');

    Route::put('/agendamento/{agendamento}', [AgendamentoController::class, 'update'])
        ->name('agendamento.update')
        ->middleware('role:lider');

    Route::patch('/agendamento/{agendamento}/cancelar', [AgendamentoController::class, 'cancelar'])
        ->name('agendamento.cancelar')
        ->middleware('role:lider');

    Route::patch('/agendamento/{agendamento}/perdido', [AgendamentoController::class, 'marcarComoPerdido'])
        ->name('agendamento.marcar-como-perdido')
        ->middleware('role:lider');

    Route::patch('/agendamento/{agendamento}/reabrir', [AgendamentoController::class, 'reabrir'])
        ->name('agendamento.reabrir')
        ->middleware('role:lider');

    Route::get('/agendamento/{agendamento}/relatorio', [HistoricoAcoesController::class, 'create'])
        ->name('historico-acoes.create')
        ->middleware('role:lider,voluntario');

    Route::post('/agendamento/{agendamento}/relatorio', [HistoricoAcoesController::class, 'store'])
        ->name('historico-acoes.store')
        ->middleware('role:lider,voluntario');

    Route::get('/relatorios/atendimentos', [RelatorioController::class, 'atendimentos'])
        ->name('relatorios.atendimentos')
        ->middleware('role:lider');

    Route::get(
        '/relatorios/beneficiarios',
        [RelatorioController::class, 'beneficiarios'])
        ->name('relatorios.beneficiarios')
        ->middleware('role:lider');

    Route::get('/usuarios', [UserController::class, 'index'])
        ->name('usuarios.index')
        ->middleware('role:lider');

    Route::get('/usuarios/criar', [UserController::class, 'create'])
        ->name('usuarios.create')
        ->middleware('role:lider');

    Route::post('/usuarios', [UserController::class, 'store'])
        ->name('usuarios.store')
        ->middleware('role:lider');

    Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])
        ->name('usuarios.edit')
        ->middleware('role:lider');

    Route::put('/usuarios/{user}', [UserController::class, 'update'])
        ->name('usuarios.update')
        ->middleware('role:lider');

    Route::patch('/usuarios/{user}/inativar', [UserController::class, 'inativar'])
        ->name('usuarios.inativar')
        ->middleware('role:lider');

    Route::patch('/usuarios/{user}/reativar', [UserController::class, 'reativar'])
        ->name('usuarios.reativar')
        ->middleware('role:lider');

    Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])
        ->name('usuarios.destroy')
        ->middleware('role:lider');
});
