<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JayI\Roster\Support\UserKey;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_transfers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('type', 32);
            $table->foreignUlid('organization_id')->nullable()->constrained('roster_organizations')->nullOnDelete();
            UserKey::column($table, 'requested_by')->index();
            $table->string('status', 32)->index();
            $table->string('impex_run_id')->nullable();
            $table->string('input_path')->nullable();
            $table->string('output_path')->nullable();
            $table->json('filters')->nullable();
            // Summary counts and the per-row plan/outcome.
            $table->json('report')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_transfers');
    }
};
