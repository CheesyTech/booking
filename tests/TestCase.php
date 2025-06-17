<?php

namespace CheeasyTech\Booking\Tests;

use CheeasyTech\Booking\BookingServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            BookingServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup booking config
        $app['config']->set('booking.overlap.enabled', true);
        $app['config']->set('booking.overlap.allow_same_booker', false);
        $app['config']->set('booking.overlap.min_time_between', 0);
        $app['config']->set('booking.overlap.max_duration', 0);
    }

    protected function setUpDatabase(): void
    {
        // Create test tables for polymorphic relations
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });
    }
}
