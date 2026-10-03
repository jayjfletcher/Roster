<?php

declare(strict_types=1);

namespace JayI\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Roster\Domains\Organization\Models\OrganizationLinkModel;

/**
 * @extends Factory<OrganizationLinkModel>
 */
final class OrganizationLinkFactory extends Factory
{
    protected $model = OrganizationLinkModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source' => 'erp',
            'external_id' => 'C-'.fake()->unique()->numberBetween(1000, 999999),
            'account_number' => 'A-'.fake()->numberBetween(1000, 99999),
        ];
    }
}
