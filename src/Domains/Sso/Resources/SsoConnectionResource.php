<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use JayI\Roster\Domains\Sso\Models\SsoConnectionModel;

/**
 * The client secret is never serialized; `client_secret` only says whether
 * one is stored.
 *
 * @mixin SsoConnectionModel
 */
final class SsoConnectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $settings = array_intersect_key($this->config, array_flip(['issuer', 'tenant', 'client_id', 'metadata_url', 'entity_id', 'sso_url']));
        ksort($settings);

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'protocol' => $this->protocol,
            'organization' => $this->organization?->slug,
            'settings' => (object) $settings,
            'client_secret' => isset($this->config['client_secret']) ? 'set' : null,
            'certificate' => isset($this->config['certificate']) ? 'set' : null,
            'jit' => $this->jit,
            'enforced' => $this->enforced,
            'enabled' => $this->enabled,
            'sign_in_url' => Route::has('roster.sso.start') ? route('roster.sso.start', $this->slug) : null,
            'callback_url' => Route::has('roster.sso.callback') ? route('roster.sso.callback', $this->slug) : null,
            'metadata_url' => $this->protocol === SsoConnectionModel::SAML && Route::has('roster.sso.metadata') ? route('roster.sso.metadata', $this->slug) : null,
            'identities_count' => $this->whenCounted('identities'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
