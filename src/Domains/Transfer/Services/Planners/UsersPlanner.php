<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Services\Planners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use JayI\Roster\Domains\User\Actions\CreateUserAction;
use JayI\Roster\Domains\User\Actions\UpdateProfileAction;
use JayI\Roster\Support\Users;

/**
 * Plain accounts: `email, name, display_name`. Existing emails are skipped;
 * passwords are never imported.
 */
final class UsersPlanner extends Planner
{
    public function __construct(private readonly Users $users) {}

    public function plan(array $values, TransferModel $transfer, ?Model $actor): array
    {
        $email = strtolower($values['email'] ?? '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->outcome(self::ERROR, __('roster::roster.import_invalid_email'));
        }

        return $this->exists($email)
            ? $this->outcome(self::SKIP, __('roster::roster.import_account_exists'))
            : $this->outcome(self::CREATE, __('roster::roster.import_new_account'));
    }

    public function apply(array $values, TransferModel $transfer, ?Model $actor): array
    {
        $plan = $this->plan($values, $transfer, $actor);

        if ($plan['action'] !== self::CREATE) {
            return $plan;
        }

        $email = strtolower($values['email']);

        $user = app(CreateUserAction::class)->execute([
            'name' => ($values['name'] ?? '') !== '' ? $values['name'] : Str::before($email, '@'),
            'email' => $email,
        ]);

        if (($values['display_name'] ?? '') !== '') {
            app(UpdateProfileAction::class)->execute($user, ['display_name' => $values['display_name']]);
        }

        return $plan;
    }

    private function exists(string $email): bool
    {
        return $this->users->findByEmail($email) !== null;
    }
}
