<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Console\Commands;

use Illuminate\Console\Command;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Role\Support\BuiltInRoles;
use RefactorCircus\Roster\Support\Users;

final class GrantSuperAdminCommand extends Command
{
    protected $signature = 'roster:grant-super-admin {email : The email of an existing user}';

    protected $description = 'Give an existing user the super-admin role';

    public function handle(Users $users): int
    {
        $email = strtolower($this->argument('email'));
        $user = $users->findByEmail($email);

        if ($user === null) {
            $this->components->error("No user has the email [{$email}].");

            return self::FAILURE;
        }

        BuiltInRoles::sync();

        $role = RoleModel::query()->where('scope', RoleScope::Global)->whereNull('organization_id')->where('slug', 'super-admin')->firstOrFail();

        RoleAssignmentModel::query()->firstOrCreate([
            'role_id' => $role->getKey(),
            'user_id' => $user->getKey(),
            'organization_id' => null,
            'team_id' => null,
        ]);

        $this->components->info("[{$email}] is now a super-admin.");

        return self::SUCCESS;
    }
}
