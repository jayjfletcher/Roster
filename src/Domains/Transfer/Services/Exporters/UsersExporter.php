<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Services\Exporters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Support\Users;

final class UsersExporter implements Exporter
{
    public function __construct(private readonly Users $users) {}

    public function headers(): array
    {
        return ['id', 'email', 'name', 'display_name', 'status', 'created_at'];
    }

    public function rows(TransferModel $transfer, ?string $after, int $limit): array
    {
        $key = $this->users->newModel()->getKeyName();

        return $this->users->query()
            ->with('rosterProfile')
            ->when($after !== null, fn (Builder $query): Builder => $query->where($key, '>', $after))
            ->orderBy($key)
            ->limit($limit)
            ->get()
            ->map(fn (Model $user): array => [
                'cursor' => (string) $user->getKey(),
                'cells' => [
                    $user->getRouteKey(),
                    $this->users->email($user),
                    $this->users->name($user),
                    $this->users->profileIfExists($user)->display_name ?? null,
                    $this->users->status($user)->value,
                    $user->getAttribute('created_at')?->toIso8601String(),
                ],
            ])
            ->all();
    }
}
