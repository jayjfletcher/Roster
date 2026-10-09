<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;

/**
 * @extends Factory<PermissionModel>
 */
final class PermissionFactory extends Factory
{
    protected $model = PermissionModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'app.'.$this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
            'system' => false,
        ];
    }
}
