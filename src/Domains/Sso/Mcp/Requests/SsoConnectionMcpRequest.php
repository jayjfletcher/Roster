<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Mcp\Requests;

use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Sso\Models\SsoConnectionModel;
use JayI\Roster\Domains\Sso\Resources\SsoConnectionResource;
use JayI\Roster\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

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
