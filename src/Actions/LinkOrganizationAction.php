<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JayI\Roster\Events\Action\OrganizationLinkedActionEvent;
use JayI\Roster\Events\Action\OrganizationLinkingActionEvent;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\OrganizationLink;

final class LinkOrganizationAction
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'source' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_.-]*$/'],
            'external_id' => ['required', 'string', 'max:191'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:191'],
        ];
    }

    /**
     * Record (or change) the organization's id and account number in one
     * external system. An id already linked to another organization is
     * refused.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Organization $organization, array $data): OrganizationLink
    {
        $source = (string) $data['source'];
        $externalId = (string) $data['external_id'];

        $taken = OrganizationLink::query()
            ->where('source', $source)
            ->where('external_id', $externalId)
            ->where('organization_id', '!=', $organization->getKey())
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['external_id' => __('roster::roster.external_id_taken', ['source' => $source])]);
        }

        OrganizationLinkingActionEvent::dispatch($organization, $data);

        $link = DB::transaction(fn (): OrganizationLink => OrganizationLink::query()->updateOrCreate(
            ['organization_id' => $organization->getKey(), 'source' => $source],
            ['external_id' => $externalId] + (array_key_exists('account_number', $data) ? ['account_number' => $data['account_number']] : []),
        ));

        OrganizationLinkedActionEvent::dispatch($organization, $source, $externalId, $link->account_number);

        return $link;
    }
}
