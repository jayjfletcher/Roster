<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Actions\StopImpersonationAction;
use JayI\Roster\Http\Resources\ImpersonationResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Impersonation;
use JayI\Roster\Models\Organization;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class StopImpersonationMcpRequest extends Request
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
            'why' => $this->self() !== null ? Impersonation::ENDED_STOPPED : Impersonation::ENDED_FORCED,
        ]);

        return Response::structured(['data' => (new ImpersonationResource($impersonation))->resolve()]);
    }

    private function impersonation(): Impersonation
    {
        return $this->resolved ??= Impersonation::query()->whereKey($this->get('impersonation'))->firstOrFail();
    }
}
