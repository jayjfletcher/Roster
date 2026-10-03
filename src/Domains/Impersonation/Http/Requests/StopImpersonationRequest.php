<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Impersonation\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use JayI\Roster\Domains\Impersonation\Actions\StopImpersonationAction;
use JayI\Roster\Domains\Impersonation\Models\ImpersonationModel;
use JayI\Roster\Domains\Impersonation\Resources\ImpersonationResource;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Http\Request;

final class StopImpersonationRequest extends Request
{
    private ?ImpersonationModel $resolved = null;

    protected function ability(): string
    {
        return 'roster.users.impersonate';
    }

    protected function scope(): ?OrganizationModel
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
            'why' => $own ? ImpersonationModel::ENDED_STOPPED : ImpersonationModel::ENDED_FORCED,
        ]);

        return (new ImpersonationResource($impersonation))->response();
    }

    private function impersonation(): ImpersonationModel
    {
        return $this->resolved ??= ImpersonationModel::query()->whereKey($this->route('impersonation'))->firstOrFail();
    }
}
