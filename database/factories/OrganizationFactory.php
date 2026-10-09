<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * @extends Factory<OrganizationModel>
 */
final class OrganizationFactory extends Factory
{
    protected $model = OrganizationModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'personal' => false,
            'auto_join' => false,
        ];
    }
}
