<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Exporters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Models\Team;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Support\Users;
use RuntimeException;

final class MembersExporter implements Exporter
{
    public function __construct(private readonly Users $users) {}

    public function headers(): array
    {
        return ['email', 'name', 'display_name', 'status', 'teams', 'roles', 'owner', 'source', 'joined_at'];
    }

    public function rows(Transfer $transfer, ?string $after, int $limit): array
    {
        $organization = $transfer->organization ?? throw new RuntimeException('A members export needs an organization.');

        $memberships = Membership::query()
            ->where('organization_id', $organization->getKey())
            ->with(['user.rosterProfile', 'teams'])
            ->when($after !== null, fn (Builder $query): Builder => $query->where('id', '>', $after))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        // Every member's organization roles for the chunk, in one query.
        $roles = RoleAssignment::query()
            ->where('organization_id', $organization->getKey())
            ->whereIn('user_id', $memberships->pluck('user_id')->all())
            ->with('role')
            ->get()
            ->groupBy(fn (RoleAssignment $assignment): string => (string) $assignment->user_id)
            ->map(fn (Collection $assignments): string => $assignments->map(fn (RoleAssignment $assignment): string => (string) $assignment->role?->slug)->sort()->implode(';'));

        return $memberships
            ->map(function (Membership $membership) use ($organization, $roles): array {
                $user = $membership->user;

                return [
                    'cursor' => $membership->id,
                    'cells' => [
                        $user instanceof Model ? $this->users->email($user) : null,
                        $user instanceof Model ? $this->users->name($user) : null,
                        $user instanceof Model ? ($this->users->profileIfExists($user)->display_name ?? null) : null,
                        $user instanceof Model ? $this->users->status($user)->value : null,
                        $membership->teams->map(fn (Team $team): string => $team->slug)->sort()->implode(';'),
                        $roles->get((string) $membership->user_id, ''),
                        $user instanceof Model && $organization->isOwnedBy($user),
                        $membership->source->value,
                        $membership->created_at?->toIso8601String(),
                    ],
                ];
            })
            ->all();
    }
}
