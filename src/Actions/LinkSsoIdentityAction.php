<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\SsoIdentityLinkedActionEvent;
use JayI\Roster\Events\Action\SsoIdentityLinkingActionEvent;
use JayI\Roster\Models\SsoConnection;
use JayI\Roster\Models\SsoIdentity;
use JayI\Roster\Sso\IdentityClaims;

final class LinkSsoIdentityAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Link the identity a signed-in user just proved at their identity
     * provider to their account.
     */
    public function execute(Model $user, SsoConnection $connection, IdentityClaims $claims): SsoIdentity
    {
        $taken = SsoIdentity::query()
            ->where('connection_id', $connection->getKey())
            ->where('subject', $claims->subject)
            ->where('user_id', '!=', $user->getKey())
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['sso' => __('roster::roster.sso_identity_taken')]);
        }

        SsoIdentityLinkingActionEvent::dispatch($user, $connection);

        $identity = DB::transaction(fn (): SsoIdentity => SsoIdentity::query()->updateOrCreate(
            ['connection_id' => $connection->getKey(), 'subject' => $claims->subject],
            ['user_id' => $user->getKey(), 'email' => $claims->email],
        ));

        SsoIdentityLinkedActionEvent::dispatch($user, $connection);

        return $identity;
    }
}
