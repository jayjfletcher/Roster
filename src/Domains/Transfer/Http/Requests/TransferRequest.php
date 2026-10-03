<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use JayI\Roster\Http\Request;

/**
 * A request about one transfer: allowed for whoever started it, or anyone
 * holding the transfer type's permission in its organization.
 */
abstract class TransferRequest extends Request
{
    private ?TransferModel $resolvedTransfer = null;

    protected function ability(): string
    {
        return $this->transfer()->type->permission();
    }

    protected function scope(): ?OrganizationModel
    {
        return $this->transfer()->organization;
    }

    protected function self(): ?Model
    {
        return $this->transfer()->requester;
    }

    protected function transfer(): TransferModel
    {
        return $this->resolvedTransfer ??= TransferModel::query()->whereKey($this->route('transfer'))->firstOrFail();
    }
}
