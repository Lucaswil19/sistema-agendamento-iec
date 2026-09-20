<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\RedefinirSenhaNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RedefinirSenhaNotification($token));
    }

    public function agendamentosCriados()
    {
        return $this->hasMany(Agendamento::class, 'user_id');
    }

    public function agendamentosResponsavel()
    {
        return $this->hasMany(Agendamento::class, 'responsavel_id');
    }

    public function historicoAcoes()
    {
        return $this->hasMany(Historico_acoes::class, 'user_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
