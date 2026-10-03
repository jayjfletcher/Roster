<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Permission\Services\Authorizer;
use JayI\Roster\Domains\User\Resources\UserSummaryResource;

/**
 * IP addresses and user agents are personal data: only global audit viewers
 * see them.
 *
 * @mixin AuditEntryModel
 */
final class AuditEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $this->actor;
        $viewer = $request->user();
        $global = app(Authorizer::class)->check($viewer instanceof Model ? $viewer : null, 'roster.audit.view');

        return [
            'id' => $this->id,
            'source' => $this->source,
            'action' => $this->action,
            'actor' => $actor instanceof Model ? (new UserSummaryResource($actor))->resolve($request) : null,
            'subject' => $this->subject_type === null ? null : [
                'type' => $this->subject_type,
                'id' => $this->subject_id,
                'label' => $this->subject_label,
            ],
            'organization' => $this->organization?->slug,
            'surface' => $this->surface,
            'ip' => $this->when($global, $this->ip),
            'user_agent' => $this->when($global, $this->user_agent),
            'changes' => $this->changes ?? (object) [],
            'context' => $this->context ?? (object) [],
            'hash' => $this->hash,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
