<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use JayI\Roster\Events\Action\ImpersonationStoppedActionEvent;
use JayI\Roster\Events\Action\ImpersonationStoppingActionEvent;
use JayI\Roster\Models\Impersonation;

final class StopImpersonationAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'why' => ['sometimes', Rule::in([Impersonation::ENDED_STOPPED, Impersonation::ENDED_EXPIRED, Impersonation::ENDED_FORCED])],
        ];
    }

    /**
     * End an impersonation, or cancel an unused link. Ending an ended one
     * changes nothing. The impersonator's browser is switched back on its
     * next request.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Impersonation $impersonation, array $data = []): Impersonation
    {
        if ($impersonation->ended_at !== null) {
            return $impersonation;
        }

        $why = is_string($data['why'] ?? null) ? $data['why'] : Impersonation::ENDED_STOPPED;

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
