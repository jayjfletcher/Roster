<?php

declare(strict_types=1);

namespace JayI\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Enums\TransferType;
use JayI\Roster\Models\Transfer;

/**
 * @extends Factory<Transfer>
 */
final class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => TransferType::ExportUsers,
            'status' => TransferStatus::Completed,
        ];
    }
}
