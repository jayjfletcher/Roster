<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Actions\Concerns\ManagesMemberships;
use JayI\Roster\Enums\MembershipSource;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Events\Action\SsoLoginFailedActionEvent;
use JayI\Roster\Events\Action\SsoLoginStartingActionEvent;
use JayI\Roster\Events\Action\SsoLoginSucceededActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\SsoConnection;
use JayI\Roster\Models\SsoIdentity;
use JayI\Roster\Sso\IdentityClaims;
use JayI\Roster\Sso\SsoLoginRefused;
use JayI\Roster\Support\Users;

final class SsoLoginAction
{
    use ManagesMemberships;

    public const string BY_IDENTITY = 'identity';

    public const string BY_LINK = 'linked';

    public const string BY_JIT = 'jit';

    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Find, link or create the account an identity provider vouched for.
     *
     * In order: an identity already linked to this subject; else an account
     * with the same email, linked automatically only when the organization
     * owns the email's domain; else a new account (just-in-time), under the
     * same domain rule. The user ends up a member of the organization.
     *
     * @return array{0: Model, 1: string}
     */
    public function execute(SsoConnection $connection, IdentityClaims $claims): array
    {
        SsoLoginStartingActionEvent::dispatch($connection);

        if (! $connection->enabled) {
            SsoLoginFailedActionEvent::dispatch($connection, $claims->email, 'disabled');

            throw ValidationException::withMessages(['sso' => __('roster::roster.sso_failed_disabled')]);
        }

        try {
            [$user, $method] = $this->signIn($connection, $claims);
        } catch (SsoLoginRefused $refused) {
            // Recorded outside the transaction, so the rollback cannot erase it.
            SsoLoginFailedActionEvent::dispatch($connection, $claims->email, $refused->reason);

            throw ValidationException::withMessages(['sso' => __('roster::roster.sso_failed_'.$refused->reason)]);
        }

        SsoLoginSucceededActionEvent::dispatch($user, $connection, $method);

        return [$user, $method];
    }

    /**
     * @return array{0: Model, 1: string}
     */
    private function signIn(SsoConnection $connection, IdentityClaims $claims): array
    {
        return DB::transaction(function () use ($connection, $claims): array {
            $identity = SsoIdentity::query()
                ->where('connection_id', $connection->getKey())
                ->where('subject', $claims->subject)
                ->first();

            if ($identity !== null && $identity->user instanceof Model) {
                [$user, $method] = [$identity->user, self::BY_IDENTITY];
            } else {
                [$user, $method] = $this->linkOrCreate($connection, $claims);
            }

            if ($this->users->status($user) !== UserStatus::Active) {
                $this->fail($connection, $claims, 'inactive');
            }

            /** @var Organization $organization */
            $organization = $connection->organization;
            $this->join($organization, $user, MembershipSource::Domain);

            SsoIdentity::query()->updateOrCreate(
                ['connection_id' => $connection->getKey(), 'subject' => $claims->subject],
                ['user_id' => $user->getKey(), 'email' => $claims->email, 'last_login_at' => now()],
            );

            return [$user, $method];
        });
    }

    /**
     * @return array{0: Model, 1: string}
     */
    private function linkOrCreate(SsoConnection $connection, IdentityClaims $claims): array
    {
        $email = $claims->email;

        if ($email === null || ! $connection->trusts($email)) {
            $this->fail($connection, $claims, 'untrusted_email');
        }

        $existing = $this->findByEmail($email);

        if ($existing !== null) {
            return [$existing, self::BY_LINK];
        }

        if (! $connection->jit) {
            $this->fail($connection, $claims, 'no_account');
        }

        $user = app(CreateUserAction::class)->execute([
            'name' => $claims->name ?? Str::before($email, '@'),
            'email' => $email,
        ]);

        // The identity provider is authoritative for this domain, so the
        // address counts as verified (where the users table tracks that).
        if ($this->hasVerifiedColumn($user) && $user->getAttribute('email_verified_at') === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return [$user, self::BY_JIT];
    }

    private function hasVerifiedColumn(Model $user): bool
    {
        return $user->getConnection()->getSchemaBuilder()->hasColumn($user->getTable(), 'email_verified_at');
    }

    private function findByEmail(string $email): ?Model
    {
        $column = $this->users->column('email');

        if ($column === null) {
            return null;
        }

        return $this->users->query()
            ->whereLike($column, $email)
            ->get()
            ->first(fn (Model $user): bool => strcasecmp((string) $this->users->email($user), $email) === 0);
    }

    private function fail(SsoConnection $connection, IdentityClaims $claims, string $reason): never
    {
        throw new SsoLoginRefused($reason);
    }
}
