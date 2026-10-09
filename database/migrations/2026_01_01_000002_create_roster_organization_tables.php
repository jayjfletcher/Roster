<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Support\UserKey;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_organizations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            // Nullable: organizations synced from external systems may have no owner yet.
            UserKey::column($table, 'owner_id')->nullable()->index();
            $table->boolean('personal')->default(false);
            $table->boolean('auto_join')->default(false);
            // The status of accounts this organization's SSO or SCIM creates.
            $table->string('provisioned_status', 16)->default('active');
            // Deleted organizations keep their slug and domains until purged.
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('roster_organization_domains', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('roster_organizations')->cascadeOnDelete();
            // One organization per domain, so auto-join is never ambiguous.
            $table->string('domain')->unique();
            $table->timestamps();
        });

        Schema::create('roster_memberships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('roster_organizations')->cascadeOnDelete();
            UserKey::column($table, 'user_id');
            $table->string('source', 32)->default('direct');
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            // A user's memberships, oldest first (their default organization).
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('roster_teams', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('roster_organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('roster_team_members', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('roster_teams')->cascadeOnDelete();
            // Keyed by the organization membership, so leaving the
            // organization removes every team seat with it.
            $table->foreignUlid('membership_id')->index()->constrained('roster_memberships')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'membership_id']);
        });

        Schema::create('roster_invitations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('roster_organizations')->cascadeOnDelete();
            $table->string('email');
            $table->string('token_hash', 64)->unique();
            $table->json('teams')->nullable();
            UserKey::column($table, 'invited_by')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'email']);
        });

        Schema::table('roster_profiles', function (Blueprint $table): void {
            $table->foreignUlid('current_organization_id')->nullable()->constrained('roster_organizations')->nullOnDelete();
            $table->foreignUlid('current_team_id')->nullable()->constrained('roster_teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roster_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_team_id');
            $table->dropConstrainedForeignId('current_organization_id');
        });

        Schema::dropIfExists('roster_invitations');
        Schema::dropIfExists('roster_team_members');
        Schema::dropIfExists('roster_teams');
        Schema::dropIfExists('roster_memberships');
        Schema::dropIfExists('roster_organization_domains');
        Schema::dropIfExists('roster_organizations');
    }
};
