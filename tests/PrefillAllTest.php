<?php

namespace Naoray\LaravelFactoryPrefill\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PrefillAllTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        $this->beforeApplicationDestroyed(function () {
            File::cleanDirectory(app_path());
            File::cleanDirectory(database_path('factories'));
        });
    }

    /**
     * Set up the database.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
        $this->loadMigrationsFrom([
            '--database' => 'mysql',
            '--path' => realpath(__DIR__ . '/migrations'),
            '--realpath' => true,
        ]);
    }

    /** @test */
    public function it_returns_a_no_files_found_error_if_no_files_were_found_in_the_given_directory()
    {
        $this->artisan('factory:all', [
            '--no-interaction' => true,
            '--path' => $directory = __DIR__ . '/Fixtures/NonExistent',
            '--realpath' => true,
            '--allow-nullable' => true,
        ])
            ->expectsOutput("No files in [$directory] were found!")
            ->assertExitCode(1);
    }

    /** @test */
    public function it_can_create_prefilled_factories_for_all_models()
    {
        $this->artisan('factory:all', [
            '--no-interaction' => true,
            '--path' => __DIR__ . '/Fixtures/Models',
            '--realpath' => true,
            '--allow-nullable' => true,
        ])->expectsOutput('3 Factories created');

        $this->assertFileExists(database_path('factories/CarFactory.php'));
        $this->assertFileExists(database_path('factories/HabitFactory.php'));
        $this->assertFileExists(database_path('factories/UserFactory.php'));
    }

    /** @test */
    public function it_can_create_prefilled_factories_for_defined_models_only_with_including_namespace()
    {
        $this->artisan('factory:all', [
            'models' => [
                '\Naoray\LaravelFactoryPrefill\Tests\Fixtures\Models\Car',
                '\Naoray\LaravelFactoryPrefill\Tests\Fixtures\Models\Habit'
            ],
            '--no-interaction' => true,
            '--allow-nullable' => true,
        ])->expectsOutput('2 Factories created');

        $this->assertFileExists(database_path('factories/CarFactory.php'));
        $this->assertFileExists(database_path('factories/HabitFactory.php'));
    }
}
