<?php

declare(strict_types=1);

namespace JayI\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Roster\Enums\UserStatus;
use JayI\Roster\Models\Profile;

/**
 * @extends Factory<Profile>
 */
final class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'display_name' => $this->faker->name(),
            'timezone' => 'UTC',
            'locale' => 'en',
            'status' => UserStatus::Active,
        ];
    }

    public function suspended(?string $reason = null): self
    {
        return $this->state([
            'status' => UserStatus::Suspended,
            'status_reason' => $reason,
            'status_changed_at' => now(),
        ]);
    }

    public function deactivated(?string $reason = null): self
    {
        return $this->state([
            'status' => UserStatus::Deactivated,
            'status_reason' => $reason,
            'status_changed_at' => now(),
        ]);
    }
}
