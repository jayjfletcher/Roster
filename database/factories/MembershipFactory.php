<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;

/**
 * @extends Factory<MembershipModel>
 */
final class MembershipFactory extends Factory
{
    protected $model = MembershipModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['source' => MembershipSource::Direct];
    }
}
