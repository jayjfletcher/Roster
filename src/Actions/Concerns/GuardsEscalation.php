<?php

declare(strict_types=1);

namespace JayI\Roster\Actions\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Access\Authorizer;
use JayI\Roster\Access\Permissions;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Team;

/**
 * Stops anyone handing out more than they hold: an actor may only grant
 * permissions - by building a role or assigning one - that they have
 * themselves in that scope. Code calls without an actor are trusted.
 */
trait GuardsEscalation
{
    /**
     * @param  array<int, string>  $permissions
     */
    private function guardEscalation(?Model $actor, array $permissions, Organization|Team|null $scope, string $field): void
    {
        if ($actor === null || ! app(Authorizer::class)->enabled()) {
            return;
        }

        $held = app(Permissions::class)->for($actor, $scope);
        $missing = array_values(array_diff($permissions, $held));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                $field => __('roster::roster.cannot_grant', ['permissions' => implode(', ', $missing)]),
            ]);
        }
    }
}
