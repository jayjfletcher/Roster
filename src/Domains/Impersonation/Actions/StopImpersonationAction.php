<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RefactorCircus\Roster\Domains\Impersonation\Events\ImpersonationStoppedActionEvent;
use RefactorCircus\Roster\Domains\Impersonation\Events\ImpersonationStoppingActionEvent;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;

final class StopImpersonationAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'why' => ['sometimes', Rule::in([ImpersonationModel::ENDED_STOPPED, ImpersonationModel::ENDED_EXPIRED, ImpersonationModel::ENDED_FORCED])],
        ];
    }

    /**
     * End an impersonation, or cancel an unused link. Ending an ended one
     * changes nothing. The impersonator's browser is switched back on its
     * next request.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(ImpersonationModel $impersonation, array $data = []): ImpersonationModel
    {
        if ($impersonation->ended_at !== null) {
            return $impersonation;
        }

        $why = is_string($data['why'] ?? null) ? $data['why'] : ImpersonationModel::ENDED_STOPPED;

        ImpersonationStoppingActionEvent::dispatch($impersonation, $data);

        DB::transaction(fn () => $impersonation->update([
            'token_hash' => null,
            'ended_at' => now(),
            'end_reason' => $why,
        ]));

        ImpersonationStoppedActionEvent::dispatch($impersonation, $why);

        return $impersonation->load(['user', 'impersonator', 'organization']);
    }
}
