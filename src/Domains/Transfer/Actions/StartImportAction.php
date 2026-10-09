<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Impex\Impex;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferStartedActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferStartingActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;

final class StartImportAction
{
    use ResolvesScopes;

    public function __construct(private readonly Transfers $transfers) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', Rule::in([TransferType::ImportMembers->value, TransferType::ImportUsers->value, TransferType::ImportTeams->value, TransferType::ImportOrganizations->value])],
            'organization' => ['sometimes', 'nullable', 'string'],
            'file' => ['sometimes', 'file', 'max:'.(int) ceil((int) config('roster.transfers.max_bytes', 5242880) / 1024)],
            'content' => ['required_without:file', 'nullable', 'string', 'max:'.(int) config('roster.transfers.max_bytes', 5242880)],
        ];
    }

    /**
     * Store the CSV and validate it. Nothing changes until the preview is
     * confirmed with ConfirmImportAction.
     *
     * @param  array<string, mixed>  $data  `type`, `organization`, and `file` (an upload) or `content` (CSV text)
     */
    public function execute(array $data, Model $actor): TransferModel
    {
        $this->transfers->ensureAvailable();

        $type = TransferType::from((string) $data['type']);
        $organization = $this->organizationFrom($data['organization'] ?? null);

        if ($type->needsOrganization() && $organization === null) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.transfer_needs_organization')]);
        }

        TransferStartingActionEvent::dispatch(array_diff_key($data, array_flip(['file', 'content'])));

        $transfer = DB::transaction(function () use ($type, $organization, $actor, $data): TransferModel {
            $transfer = TransferModel::query()->create([
                'type' => $type,
                'organization_id' => Transfers::scope($type, $organization)?->getKey(),
                'requested_by' => $actor->getKey(),
                'status' => TransferStatus::Validating,
            ]);

            $path = 'roster/transfers/'.$transfer->id.'/import.csv';
            $file = $data['file'] ?? null;

            $file instanceof UploadedFile
                ? $this->transfers->disk()->putFileAs(dirname($path), $file, basename($path))
                : $this->transfers->disk()->put($path, (string) ($data['content'] ?? ''));

            $transfer->update(['input_path' => $path]);

            return $transfer;
        });

        $run = app(Impex::class)->run(
            slug: Transfers::IMPORT_FLOW,
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
