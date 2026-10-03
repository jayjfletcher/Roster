<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells someone their account has been approved.
 */
final class UserApprovedNotification extends Notification
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('roster::roster.approved_subject', ['app' => (string) config('app.name')]))
            ->line(__('roster::roster.approved_line', ['app' => (string) config('app.name')]))
            ->action(__('roster::roster.approved_action'), url('/'));
    }
}
