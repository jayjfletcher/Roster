<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // An organization's identity in an external system of record (an ERP,
        // a CRM, ...): one row per organization per source.
        Schema::create('roster_organization_links', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('roster_organizations')->cascadeOnDelete();
            $table->string('source', 64);
            $table->string('external_id', 191);
            $table->string('account_number', 191)->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->unique(['organization_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_organization_links');
    }
};
