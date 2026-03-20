<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Tests;

use GoodMaven\Anvil\Concerns\TestableWorkbench;
use GoodMaven\Arabicable\Concerns\HasArabicableMigrationBlueprintMacros;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use HasArabicableMigrationBlueprintMacros;
    use LazilyRefreshDatabase;
    use TestableWorkbench;
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function getEnvironmentSetUp($app): void
    {
        $this->setDatabaseTestingEssentials();
        $this->arabicableMigrationBlueprintMacros();
    }

    protected function defineDatabaseMigrations(): void
    {
        //
    }
}
