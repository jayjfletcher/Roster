<?php

declare(strict_types=1);

namespace JayI\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JayI\Roster\Domains\Role\Enums\RoleScope;
use JayI\Roster\Domains\Role\Models\RoleModel;

/**
 * @extends Factory<RoleModel>
 */
final class RoleFactory extends Factory
{
    protected $model = RoleModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->jobTitle();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'scope' => RoleScope::Global,
        ];
    }
}
