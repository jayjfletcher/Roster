<?php

declare(strict_types=1);

namespace JayI\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JayI\Roster\Enums\RoleScope;
use JayI\Roster\Models\Role;

/**
 * @extends Factory<Role>
 */
final class RoleFactory extends Factory
{
    protected $model = Role::class;

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
