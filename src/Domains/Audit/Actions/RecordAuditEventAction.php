<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JayI\Roster\Domains\Audit\Events\AuditEventRecordedActionEvent;
use JayI\Roster\Domains\Audit\Events\AuditEventRecordingActionEvent;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Audit\Services\AuditLog;
use JayI\Roster\Domains\Audit\Services\AuditRecorder;
use JayI\Roster\Domains\Audit\Services\Snapshots;
use JayI\Roster\Domains\Audit\Services\Surface;
use JayI\Roster\Domains\Impersonation\Services\ImpersonationContext;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;
use JayI\Roster\Support\Concerns\ResolvesScopes;

final class RecordAuditEventAction
{
    use ResolvesScopes;

    public function __construct(
        private readonly AuditLog $log,
        private readonly Snapshots $snapshots,
        private readonly Surface $surface,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'action' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_-]+(\.[a-z0-9_-]+)+$/'],
            'subject_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'subject_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'subject_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'organization' => ['sometimes', 'nullable', 'string'],
            'changes' => ['sometimes', 'array'],
            'changes.*' => ['array', 'size:2'],
            'context' => ['sometimes', 'array'],
        ];
    }

    /**
     * Record one of the app's own events in the audit log, e.g.
     * `invoice.paid`. Always recorded with source `app`, so it can never pass
     * for an entry Roster made. Pass `$subject` / `$organization` models from
     * code, or `subject_*` / `organization` (slug) in `$data`.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Model $actor = null, ?Model $subject = null, ?OrganizationModel $organization = null): AuditEntryModel
    {
        AuditEventRecordingActionEvent::dispatch($data);

        $organization ??= $this->organizationFrom($data['organization'] ?? null);
        $surface = $this->surface->current();
        $request = in_array($surface, ['http', 'mcp', 'atrium', 'web'], true) ? $this->surface->request() : null;

        $entry = $this->log->append([
            'source' => AuditEntryModel::SOURCE_APP,
            'action' => (string) $data['action'],
            'actor_id' => $actor?->getKey(),
            'subject_type' => $subject?->getMorphClass() ?? $this->string($data['subject_type'] ?? null),
            'subject_id' => $subject === null ? $this->string($data['subject_id'] ?? null) : (string) $subject->getKey(),
            'subject_label' => $subject === null
                ? $this->string($data['subject_label'] ?? null)
                : app(AuditRecorder::class)->label($subject),
            'organization_id' => $organization?->getKey(),
            'surface' => $surface,
            'ip' => $request?->ip(),
            'user_agent' => $request === null ? null : Str::limit((string) $request->userAgent(), 500, ''),
            'changes' => $this->snapshots->redact((array) ($data['changes'] ?? [])),
            'context' => $this->snapshots->redact($this->context($data)),
        ]);

        AuditEventRecordedActionEvent::dispatch($entry);

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function context(array $data): array
    {
        $context = (array) ($data['context'] ?? []);
        $impersonator = app(ImpersonationContext::class)->impersonator();

        return $impersonator === null ? $context : $context + [
            'impersonator' => ['id' => (string) $impersonator->getKey(), 'label' => app(AuditRecorder::class)->label($impersonator)],
        ];
    }

    private function string(mixed $value): ?string
    {
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }
}
