<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Data;

use Illuminate\Database\Eloquent\Model;
use JayI\Roster\Domains\Audit\Actions\RecordAuditEventAction;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * Builds one of the app's own audit entries:
 *
 *     Roster::audit('invoice.paid')->on($invoice)->in($organization)->with(['amount' => 100])->record();
 */
final class PendingAuditEntry
{
    private ?Model $subject = null;

    private ?OrganizationModel $organization = null;

    /** @var array<string, mixed> */
    private array $context = [];

    /** @var array<string, array{0: mixed, 1: mixed}> */
    private array $changes = [];

    public function __construct(
        private readonly string $action,
        private ?Model $actor = null,
    ) {}

    /**
     * What the event happened to.
     */
    public function on(Model $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * The organization the event belongs to, so its admins can see it.
     */
    public function in(?OrganizationModel $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Who did it. Defaults to the signed-in user.
     */
    public function by(?Model $actor): self
    {
        $this->actor = $actor;

        return $this;
    }

    /**
     * Extra details to keep with the entry.
     *
     * @param  array<string, mixed>  $context
     */
    public function with(array $context): self
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * Field changes, as `field => [old, new]`.
     *
     * @param  array<string, array{0: mixed, 1: mixed}>  $changes
     */
    public function changes(array $changes): self
    {
        $this->changes = array_merge($this->changes, $changes);

        return $this;
    }

    public function record(): AuditEntryModel
    {
        $data = ['action' => $this->action, 'context' => $this->context, 'changes' => $this->changes];

        validator($data, RecordAuditEventAction::rules())->validate();

        return app(RecordAuditEventAction::class)->execute($data, $this->actor, $this->subject, $this->organization);
    }
}
