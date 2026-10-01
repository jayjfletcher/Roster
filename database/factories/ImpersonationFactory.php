<?php

declare(strict_types=1);

namespace JayI\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Roster\Models\Impersonation;

/**
 * @extends Factory<Impersonation>
 */
final class ImpersonationFactory extends Factory
{
    protected $model = Impersonation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reason' => 'Support ticket',
            'link_expires_at' => now()->addMinutes(5),
        ];
    }

    public function started(): self
    {
        return $this->state(['started_at' => now(), 'expires_at' => now()->addMinutes(30)]);
    }
}
