<?php

declare(strict_types=1);

namespace JayI\Roster\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use JayI\Roster\Models\Invitation;
use JayI\Roster\Models\Organization;
use SensitiveParameter;

/**
 * Emails an invitation link. Sent to the invited address, which need not
 * belong to a registered user yet.
 */
final class InvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Invitation $invitation,
        #[SensitiveParameter]
        public readonly string $token,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var Organization $organization */
        $organization = $this->invitation->organization;

        return (new MailMessage)
            ->subject(__('roster::roster.invitation_subject', ['organization' => $organization->name]))
            ->line(__('roster::roster.invitation_line', ['organization' => $organization->name]))
            ->action(__('roster::roster.invitation_action'), $this->url())
            ->line(__('roster::roster.invitation_expires', ['date' => $this->invitation->expires_at->toDayDateTimeString()]));
    }

    /**
     * The accept link: the app's own page when `roster.invitations.accept_url`
     * is set, otherwise Roster's signed invitation page.
     */
    public function url(): string
    {
        $custom = config('roster.invitations.accept_url');

        if (is_string($custom) && $custom !== '') {
            return str_replace('{token}', $this->token, $custom);
        }

        return URL::temporarySignedRoute('roster.invitations.show', $this->invitation->expires_at, ['token' => $this->token]);
    }
}
