<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StopImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Impersonation\Resources\ImpersonationResource;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Mcp\Request;

final class StopImpersonationMcpRequest extends Request
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

    protected function self(): ?Model
    {
        $actor = $this->actor();

        return $actor !== null && (string) $this->impersonation()->impersonator_id === (string) $actor->getKey() ? $actor : null;
    }

    protected function rules(): array
    {
        return ['impersonation' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $impersonation = app(StopImpersonationAction::class)->execute($this->impersonation(), [
            'why' => $this->self() !== null ? ImpersonationModel::ENDED_STOPPED : ImpersonationModel::ENDED_FORCED,
        ]);

        return Response::structured(['data' => (new ImpersonationResource($impersonation))->resolve()]);
    }

    private function impersonation(): ImpersonationModel
    {
        return $this->resolved ??= ImpersonationModel::query()->whereKey($this->get('impersonation'))->firstOrFail();
    }
}
