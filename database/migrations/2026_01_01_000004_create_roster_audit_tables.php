<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Support\UserKey;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_audit_entries', function (Blueprint $table): void {
            // Auto-increment rather than ULID: the hash chain needs a strict,
            // gap-free order.
            $table->id();
            $table->string('source', 16)->default('roster');
            $table->string('action');
            UserKey::column($table, 'actor_id')->nullable()->index();
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            // No foreign key: history outlives the organization.
            $table->ulid('organization_id')->nullable();
            $table->string('surface', 16);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('changes')->nullable();
            $table->json('context')->nullable();
            $table->string('previous_hash', 64)->nullable();
            $table->string('hash', 64);
            $table->timestamp('created_at');

            $table->index(['organization_id', 'id']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
            $table->index('created_at');
        });

        // The head of the hash chain: one row every writer locks, so
        // concurrent appends chain one after another on every database
        // (locking the newest entry instead forks the chain on Postgres, and
        // locks nothing while the log is empty).
        Schema::create('roster_audit_chain', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('head_hash', 64)->nullable();
        });

        DB::table('roster_audit_chain')->insert(['id' => 1, 'head_hash' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_audit_chain');
        Schema::dropIfExists('roster_audit_entries');
    }
};
