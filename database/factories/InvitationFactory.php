<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;

/**
 * @extends Factory<InvitationModel>
 */
final class InvitationFactory extends Factory
{
    protected $model = InvitationModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'token_hash' => hash('sha256', Str::random(40)),
            'teams' => [],
            'expires_at' => now()->addDays(7),
        ];
    }
}
