<?php

declare(strict_types=1);

namespace JayI\Roster\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JayI\Roster\Domains\Transfer\Enums\TransferStatus;
use JayI\Roster\Domains\Transfer\Enums\TransferType;
use JayI\Roster\Domains\Transfer\Models\TransferModel;

/**
 * @extends Factory<TransferModel>
 */
final class TransferFactory extends Factory
{
    protected $model = TransferModel::class;

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
