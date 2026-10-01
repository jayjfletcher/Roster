<?php

declare(strict_types=1);

namespace JayI\Roster\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Enums\RoleScope;
use JayI\Roster\Models\Role;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Support\BuiltInRoles;
use JayI\Roster\Support\Users;

final class GrantSuperAdminCommand extends Command
{
    protected $signature = 'roster:grant-super-admin {email : The email of an existing user}';

    protected $description = 'Give an existing user the super-admin role';

    public function handle(Users $users): int
    {
        $email = strtolower($this->argument('email'));
        $column = $users->column('email') ?? 'email';

        $user = $users->query()
            ->whereLike($column, $email)
            ->get()
            ->first(fn (Model $candidate): bool => strcasecmp((string) $users->email($candidate), $email) === 0);

        if ($user === null) {
            $this->components->error("No user has the email [{$email}].");

            return self::FAILURE;
        }

        BuiltInRoles::sync();

        $role = Role::query()->where('scope', RoleScope::Global)->whereNull('organization_id')->where('slug', 'super-admin')->firstOrFail();

        RoleAssignment::query()->firstOrCreate([
            'role_id' => $role->getKey(),
            'user_id' => $user->getKey(),
            'organization_id' => null,
            'team_id' => null,
        ]);

        $this->components->info("[{$email}] is now a super-admin.");

        return self::SUCCESS;
    }
}
