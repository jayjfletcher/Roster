<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers\Exporters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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

        return Membership::query()
            ->where('organization_id', $organization->getKey())
            ->with(['user', 'teams'])
            ->when($after !== null, fn (Builder $query): Builder => $query->where('id', '>', $after))
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(function (Membership $membership) use ($organization): array {
                $user = $membership->user;
                $roles = $user instanceof Model ? RoleAssignment::query()
                    ->where('user_id', $user->getKey())
                    ->where('organization_id', $organization->getKey())
                    ->with('role')
                    ->get()
                    ->map(fn (RoleAssignment $assignment): string => (string) $assignment->role?->slug)
                    ->sort()
                    ->implode(';') : '';

                return [
                    'cursor' => $membership->id,
                    'cells' => [
                        $user instanceof Model ? $this->users->email($user) : null,
                        $user instanceof Model ? $this->users->name($user) : null,
                        $user instanceof Model ? ($this->users->profileIfExists($user)->display_name ?? null) : null,
                        $user instanceof Model ? $this->users->status($user)->value : null,
                        $membership->teams->map(fn (Team $team): string => $team->slug)->sort()->implode(';'),
                        $roles,
                        $user instanceof Model && $organization->isOwnedBy($user),
                        $membership->source->value,
                        $membership->created_at?->toIso8601String(),
                    ],
                ];
            })
            ->all();
    }
}
