<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;
use RefactorCircus\Roster\Mcp\Request;

abstract class SsoConnectionMcpRequest extends Request
{
    private ?SsoConnectionModel $resolved = null;

    protected function connection(): SsoConnectionModel
    {
        return $this->resolved ??= SsoConnectionModel::query()->where('slug', $this->get('connection'))->firstOrFail();
    }

    protected function scope(): ?OrganizationModel
    {
        return $this->connection()->organization;
    }

    protected function respondWithConnection(SsoConnectionModel $connection): ResponseFactory
    {
        return Response::structured(['data' => (new SsoConnectionResource($connection))->resolve()]);
    }
}
