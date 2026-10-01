<?php

declare(strict_types=1);

namespace JayI\Roster\Mcp\Requests;

use JayI\Roster\Enums\TransferType;
use JayI\Roster\Mcp\Request;
use JayI\Roster\Models\Organization;
use JayI\Roster\Transfers\Transfers;

/**
 * Starting an import or export needs the type's permission, in the
 * organization for organization-wide types.
 */
abstract class StartTransferMcpRequest extends Request
{
    /**
     * The permission checked while the type is not valid yet; validation
     * then rejects it.
     */
    abstract protected function fallbackAbility(): string;

    protected function ability(): string
    {
        return $this->type()?->permission() ?? $this->fallbackAbility();
    }

    protected function scope(): ?Organization
    {
        $type = $this->type();
        $slug = $this->get('organization');

        if ($type === null || ! is_string($slug) || $slug === '') {
            return null;
        }

        return Transfers::scope($type, Organization::query()->where('slug', $slug)->first());
    }

    private function type(): ?TransferType
    {
        $type = $this->get('type');

        return is_string($type) ? TransferType::tryFrom($type) : null;
    }
}
