<?php

declare(strict_types=1);

namespace JayI\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JayI\Roster\Domains\Team\Models\TeamModel;

/**
 * @extends Factory<TeamModel>
 */
final class TeamFactory extends Factory
{
    protected $model = TeamModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->jobTitle();

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
        ];
    }
}
