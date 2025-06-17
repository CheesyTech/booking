#!/bin/bash

# Ensure we're in the package root
cd "$(dirname "$0")"

# Install dev dependencies if needed
composer install --dev

# Create SQLite database if it doesn't exist
touch database.sqlite

# Run artisan through testbench to generate PHPDoc for package models
php -r "
require __DIR__ . '/vendor/autoload.php';
\$app = require __DIR__ . '/vendor/orchestra/testbench-core/laravel/bootstrap/app.php';

\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Configure database
\$app['config']->set('database.default', 'sqlite');
\$app['config']->set('database.connections.sqlite', [
    'driver' => 'sqlite',
    'database' => __DIR__ . '/database.sqlite',
    'prefix' => '',
]);

// Register service providers
\$app->register(Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
\$app->register(CheeasyTech\Booking\BookingServiceProvider::class);

// Run migrations if needed
\$app->make(Illuminate\Contracts\Console\Kernel::class)->call('migrate:fresh', [
    '--path' => 'migrations',
    '--force' => true
]);

// Generate PHPDoc
\$app->make(Illuminate\Contracts\Console\Kernel::class)->call('ide-helper:models', [
    '--write' => true,
    '--nowrite' => false,
    '--reset' => false,
    '--dir' => ['src', 'tests/Models']
]);
" 