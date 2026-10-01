<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Actions\StopImpersonationAction;
use JayI\Roster\Http\Request;
use JayI\Roster\Http\Resources\ImpersonationResource;
use JayI\Roster\Models\Impersonation;
use JayI\Roster\Models\Organization;

final class StopImpersonationRequest extends Request
{
    private ?Impersonation $resolved = null;

    protected function ability(): string
    {
        return 'roster.users.impersonate';
    }

    protected function scope(): ?Organization
    {
        return $this->impersonation()->organization;
    }

    /**
     * Impersonators may always end their own.
     */
    protected function self(): ?Model
    {
        $actor = $this->actor();

        return $actor !== null && (string) $this->impersonation()->impersonator_id === (string) $actor->getKey() ? $actor : null;
    }

    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        $own = $this->self() !== null;

        $impersonation = app(StopImpersonationAction::class)->execute($this->impersonation(), [
            'why' => $own ? Impersonation::ENDED_STOPPED : Impersonation::ENDED_FORCED,
        ]);

        return (new ImpersonationResource($impersonation))->response();
    }

    private function impersonation(): Impersonation
    {
        return $this->resolved ??= Impersonation::query()->whereKey($this->route('impersonation'))->firstOrFail();
    }
}
