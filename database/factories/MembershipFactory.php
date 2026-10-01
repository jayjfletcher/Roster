<?php

declare(strict_types=1);

namespace JayI\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Roster\Enums\MembershipSource;
use JayI\Roster\Models\Membership;

/**
 * @extends Factory<Membership>
 */
final class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['source' => MembershipSource::Direct];
    }
}
