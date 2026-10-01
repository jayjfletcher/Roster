<?php

declare(strict_types=1);

namespace JayI\Roster\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Http\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Transfer;

/**
 * A request about one transfer: allowed for whoever started it, or anyone
 * holding the transfer type's permission in its organization.
 */
abstract class TransferRequest extends Request
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
        return $this->resolvedTransfer ??= Transfer::query()->whereKey($this->route('transfer'))->firstOrFail();
    }
}
