<?php

declare(strict_types=1);

namespace JayI\Roster\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JayI\Impex\Impex;
use JayI\Roster\Actions\Concerns\ResolvesScopes;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Enums\TransferType;
use JayI\Roster\Events\Action\TransferStartedActionEvent;
use JayI\Roster\Events\Action\TransferStartingActionEvent;
use JayI\Roster\Models\AuditEntry;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Transfers\Transfers;

final class StartExportAction
{
    use ResolvesScopes;

    public function __construct(private readonly Transfers $transfers) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', Rule::in([TransferType::ExportMembers->value, TransferType::ExportUsers->value, TransferType::ExportAudit->value, TransferType::ExportOrganizations->value])],
            'organization' => ['sometimes', 'nullable', 'string'],
            'filters' => ['sometimes', 'array'],
            'filters.source' => ['sometimes', 'nullable', Rule::in([AuditEntry::SOURCE_ROSTER, AuditEntry::SOURCE_APP])],
            'filters.action' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filters.since' => ['sometimes', 'nullable', 'date'],
            'filters.until' => ['sometimes', 'nullable', 'date'],
            'filters.external_source' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }

    /**
     * Build a CSV export in the background. An audit export with an
     * organization covers only that organization's entries.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, Model $actor): Transfer
    {
        $this->transfers->ensureAvailable();

        $type = TransferType::from((string) $data['type']);
        $organization = $this->organizationFrom($data['organization'] ?? null);

        if ($type->needsOrganization() && $organization === null) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.transfer_needs_organization')]);
        }

        TransferStartingActionEvent::dispatch($data);

        $transfer = DB::transaction(fn (): Transfer => Transfer::query()->create([
            'type' => $type,
            'organization_id' => Transfers::scope($type, $organization)?->getKey(),
            'requested_by' => $actor->getKey(),
            'status' => TransferStatus::Running,
            'filters' => match ($type) {
                TransferType::ExportAudit => array_diff_key((array) ($data['filters'] ?? []), ['external_source' => true]),
                TransferType::ExportOrganizations => array_intersect_key((array) ($data['filters'] ?? []), ['external_source' => true]),
                default => null,
            },
        ]));

        $run = app(Impex::class)->run(
            slug: Transfers::EXPORT_FLOW,
            arguments: [$transfer->id],
            tags: ['roster_transfer' => $transfer->id],
            owners: array_filter(['requester' => $actor, 'organization' => $organization]),
        );

        $transfer->update(['impex_run_id' => (string) $run->getKey()]);
        $transfer = $transfer->refresh();

        TransferStartedActionEvent::dispatch($transfer);

        return $transfer;
    }
}
