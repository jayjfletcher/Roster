<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Http\Requests;

use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Sso\Models\SsoConnectionModel;
use JayI\Roster\Http\Request;

abstract class SsoConnectionRequest extends Request
{
    private ?SsoConnectionModel $resolved = null;

    protected function connection(): SsoConnectionModel
    {
        return $this->resolved ??= SsoConnectionModel::query()->where('slug', $this->route('connection'))->firstOrFail();
    }

    protected function scope(): ?OrganizationModel
    {
        return $this->connection()->organization;
    }
}
