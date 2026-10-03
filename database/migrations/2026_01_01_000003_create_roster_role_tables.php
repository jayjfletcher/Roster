<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JayI\Roster\Domains\Role\Support\BuiltInRoles;
use JayI\Roster\Support\UserKey;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_permissions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->string('description', 1000)->nullable();
            $table->boolean('system')->default(false);
            $table->timestamps();
        });

        Schema::create('roster_roles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug');
            $table->string('scope', 32);
            // Null for a role every organization can use; set for one
            // organization's own custom role.
            $table->foreignUlid('organization_id')->nullable()->constrained('roster_organizations')->cascadeOnDelete();
            $table->string('description', 1000)->nullable();
            $table->boolean('super')->default(false);
            $table->boolean('system')->default(false);
            $table->timestamps();

            $table->index(['scope', 'organization_id', 'slug']);
        });

        Schema::create('roster_permission_role', function (Blueprint $table): void {
            $table->foreignUlid('role_id')->constrained('roster_roles')->cascadeOnDelete();
            $table->foreignUlid('permission_id')->constrained('roster_permissions')->cascadeOnDelete();

            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('roster_role_assignments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('role_id')->constrained('roster_roles')->cascadeOnDelete();
            UserKey::column($table, 'user_id');
            $table->foreignUlid('organization_id')->nullable()->index()->constrained('roster_organizations')->cascadeOnDelete();
            $table->foreignUlid('team_id')->nullable()->index()->constrained('roster_teams')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'user_id', 'organization_id', 'team_id']);
            // A user's permissions in a scope: every Gate check reads this.
            $table->index(['user_id', 'organization_id', 'team_id']);
        });

        BuiltInRoles::sync();
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_role_assignments');
        Schema::dropIfExists('roster_permission_role');
        Schema::dropIfExists('roster_roles');
        Schema::dropIfExists('roster_permissions');
    }
};
