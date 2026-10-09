<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;

/**
 * @extends Factory<ImpersonationModel>
 */
final class ImpersonationFactory extends Factory
{
    protected $model = ImpersonationModel::class;

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
