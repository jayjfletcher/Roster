<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\User\Concerns;

/**
 * Profile field rules, shared by every Action that writes a profile.
 */
trait ProfileRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected static function profileRules(): array
    {
        return [
            'display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'timezone' => ['sometimes', 'nullable', 'timezone:all'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:16'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'meta' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function profileAttributes(array $data): array
    {
        return array_intersect_key($data, array_flip(['display_name', 'avatar_url', 'timezone', 'locale', 'bio', 'meta']));
    }
}
