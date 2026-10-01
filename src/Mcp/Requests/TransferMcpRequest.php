<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Transfer;

/**
 * A call about one transfer: allowed for whoever started it, or anyone
 * holding the transfer type's permission in its organization.
 */
abstract class TransferMcpRequest extends Request
{
    private ?Transfer $resolvedTransfer = null;

    protected function ability(): string
    {
        return $this->transfer()->type->permission();
    }

    protected function scope(): ?Organization
    {
        return $this->transfer()->organization;
    }

    protected function self(): ?Model
    {
        return $this->transfer()->requester;
    }

    protected function transfer(): Transfer
    {
        return $this->resolvedTransfer ??= Transfer::query()->whereKey($this->get('transfer'))->firstOrFail();
    }
}
