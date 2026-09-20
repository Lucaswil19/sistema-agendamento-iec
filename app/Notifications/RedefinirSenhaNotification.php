<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RedefinirSenhaNotification extends Notification
{
    public function __construct(public readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $broker = config('auth.defaults.passwords');
        $expiration = (int) config("auth.passwords.{$broker}.expire", 60);
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Redefinição de senha — Sistema de Agendamento IEC')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Recebemos uma solicitação para redefinir a senha de acesso ao '.config('app.name').'.')
            ->action('Redefinir senha', $url)
            ->line("Este link expira em {$expiration} minutos e pode ser utilizado uma única vez.")
            ->line('Caso você não tenha solicitado a redefinição, ignore esta mensagem. Sua senha permanecerá inalterada.')
            ->salutation('Equipe '.config('app.name'));
    }
}
