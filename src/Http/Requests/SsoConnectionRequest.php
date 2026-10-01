<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use JayI\Roster\Http\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\SsoConnection;

abstract class SsoConnectionRequest extends Request
{
    private ?SsoConnection $resolved = null;

    protected function connection(): SsoConnection
    {
        return $this->resolved ??= SsoConnection::query()->where('slug', $this->route('connection'))->firstOrFail();
    }

    protected function scope(): ?Organization
    {
        return $this->connection()->organization;
    }
}
