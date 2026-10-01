<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Http\Resources\SsoConnectionResource;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\SsoConnection;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

abstract class SsoConnectionMcpRequest extends Request
{
    private ?SsoConnection $resolved = null;

    protected function connection(): SsoConnection
    {
        return $this->resolved ??= SsoConnection::query()->where('slug', $this->get('connection'))->firstOrFail();
    }

    protected function scope(): ?Organization
    {
        return $this->connection()->organization;
    }

    protected function respondWithConnection(SsoConnection $connection): ResponseFactory
    {
        return Response::structured(['data' => (new SsoConnectionResource($connection))->resolve()]);
    }
}
