<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Impersonation\Events\ImpersonationEnteredActionEvent;
use RefactorCircus\Roster\Domains\Impersonation\Events\ImpersonationEnteringActionEvent;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Support\Users;

final class EnterImpersonationAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Use a one-time link: only once, before it expires, by the impersonator
     * who asked for it, while the user is still active. Starts the clock.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, Model $actor): ImpersonationModel
    {
        $impersonation = ImpersonationModel::query()->where('token_hash', hash('sha256', (string) ($data['token'] ?? '')))->first();

        if ($impersonation === null || $impersonation->started_at !== null || $impersonation->ended_at !== null || $impersonation->link_expires_at->isPast()) {
            throw ValidationException::withMessages(['token' => __('roster::roster.impersonation_link_invalid')]);
        }

        if ((string) $impersonation->impersonator_id !== (string) $actor->getKey()) {
            throw ValidationException::withMessages(['token' => __('roster::roster.impersonation_link_wrong_user')]);
        }

        $user = $impersonation->user;

        if (! $user instanceof Model || $this->users->status($user) !== UserStatus::Active) {
            throw ValidationException::withMessages(['token' => __('roster::roster.impersonate_inactive')]);
        }

        ImpersonationEnteringActionEvent::dispatch($impersonation);

        DB::transaction(fn () => $impersonation->update([
            'token_hash' => null,
            'started_at' => now(),
            'expires_at' => now()->addMinutes((int) config('roster.impersonation.ttl_minutes', 30)),
        ]));

        ImpersonationEnteredActionEvent::dispatch($impersonation);

        return $impersonation;
    }
}
