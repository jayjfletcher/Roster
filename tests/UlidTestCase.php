<?php

declare(strict_types=1);

namespace JayI\Roster\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JayI\Roster\Tests\Fixtures\UlidUser;

/**
 * A host user model keyed by ULID, with roster_profiles migrated to match.
 */
abstract class UlidTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('roster.users.model', UlidUser::class);
        $app['config']->set('roster.users.key_type', 'ulid');
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->beforeApplicationDestroyed(fn () => Schema::dropIfExists('ulid_users'));

        Schema::create('ulid_users', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('full_name');
            $table->string('email_address')->unique();
            $table->string('secret');
            $table->timestamps();
        });
    }
}
