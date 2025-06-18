# Laravel Booking

A flexible and powerful booking system for Laravel applications that supports polymorphic relationships, time slot management, and advanced status tracking.

## Features

- 🕒 Time slot management with overlap prevention
- 🔄 Polymorphic relationships for bookable and bookerable resources
- ⚙️ Configurable validation and overlap rules
- 🎯 Custom booking rules via OverlapRule interface
- 📅 Business hours and custom rules validation
- 🔒 Booking duration and minimum interval limits
- 🎨 Easy to extend with traits and interfaces
- 📊 Status tracking with full history and metadata
- 🔄 Event-driven architecture (BookingCreated, BookingUpdated, etc.)
- 🧪 Comprehensive testing support and model factories
- 🔍 Advanced querying and duration-based scopes (cross-DB)
- 💾 Multi-database support (MySQL, PostgreSQL, SQLite, SQL Server)
- ⏱️ Flexible duration calculations (minutes, hours, days)
- 🛠️ Publishable config and migration files

## Requirements

- PHP 8.1 or higher
- Laravel 10.0, 11.0, or 12.0
- Carbon 2.0 or higher

## Installation

1. Install the package via composer:

```bash
composer require cheesytech/booking
```

2. The package will automatically register its service provider.

3. Publish and run the migrations:

```bash
php artisan vendor:publish --provider="CheeasyTech\Booking\BookingServiceProvider" --tag="migrations"
php artisan migrate
```

4. Publish the configuration file:

```bash
php artisan vendor:publish --provider="CheeasyTech\Booking\BookingServiceProvider" --tag="config"
```

Or use the install command for all at once:

```bash
php artisan package:install cheesytech/booking
```

This will create a `config/booking.php` file in your config directory.

## Configuration

The package is highly configurable through the `config/booking.php` file. Example:

```php
return [
    'statuses' => [
        'pending' => [
            'label' => 'Pending',
            'color' => '#FFA500',
            'can_transition_to' => ['confirmed', 'cancelled'],
        ],
        'confirmed' => [
            'label' => 'Confirmed',
            'color' => '#008000',
            'can_transition_to' => ['cancelled', 'completed'],
        ],
        'cancelled' => [
            'label' => 'Cancelled',
            'color' => '#FF0000',
            'can_transition_to' => [],
        ],
        'completed' => [
            'label' => 'Completed',
            'color' => '#0000FF',
            'can_transition_to' => [],
        ],
    ],
    'overlap' => [
        'enabled' => true,
        'allow_same_booker' => false,
        'min_time_between' => 0,
        'max_duration' => 0,
        'rules' => [
            'business_hours' => [
                'enabled' => false,
                'class' => \CheeasyTech\Booking\Rules\BusinessHoursRule::class,
            ],
        ],
    ],
    'events' => [
        'enabled' => true,
        'classes' => [
            'created' => \CheeasyTech\Booking\Events\BookingCreated::class,
            'updated' => \CheeasyTech\Booking\Events\BookingUpdated::class,
            'deleted' => \CheeasyTech\Booking\Events\BookingDeleted::class,
            'status_changed' => \CheeasyTech\Booking\Events\BookingStatusChanged::class,
        ],
    ],
];
```

## Model Setup

Implement the provided interfaces and use the traits for your models:

```php
use CheeasyTech\Booking\Contracts\Bookable;
use CheeasyTech\Booking\Traits\HasBookings;
use Illuminate\Database\Eloquent\Model;

class Room extends Model implements Bookable {
    use HasBookings;
    // ...
    public function getBookableId(): int { return $this->id; }
    public function getBookableType(): string { return static::class; }
}

use CheeasyTech\Booking\Contracts\Bookerable;
use CheeasyTech\Booking\Traits\HasBookers;

class User extends Model implements Bookerable {
    use HasBookers;
    // ...
    public function getBookerableId(): int|string { return $this->id; }
    public function getBookerableType(): string { return static::class; }
}
```

## Quick Start

```php
use CheeasyTech\Booking\Models\Booking;
use Carbon\Carbon;

$room = Room::find(1);
$user = User::find(1);

$booking = new Booking();
$booking->bookable()->associate($room);
$booking->bookerable()->associate($user);
$booking->start_time = Carbon::tomorrow()->setHour(10);
$booking->end_time = Carbon::tomorrow()->setHour(11);
$booking->status = 'pending';
$booking->save();

// Change booking status
$booking->changeStatus('confirmed', 'Approved by admin', ['key' => 'value']);

// Check for overlap
$isAvailable = !$booking->hasOverlap(
    Carbon::tomorrow()->setHour(10),
    Carbon::tomorrow()->setHour(11)
);
```

## Traits & Interfaces

- **HasBookings**: Add to bookable models (e.g., Room) for convenient booking management (`newBooking`, `deleteBooking`, etc.).
- **HasBookers**: Add to bookerable models (e.g., User) for managing bookings made by the entity.
- **Bookable**: Interface for resources to be booked (must implement `getBookableId`, `getBookableType`).
- **Bookerable**: Interface for entities making bookings (must implement `getBookerableId`, `getBookerableType`).
- **OverlapRule**: Interface for custom overlap validation rules.

## Database Schema

The package creates the following database table:

```php
Schema::create('bookings', function (Blueprint $table) {
    $table->id();
    $table->morphs('bookable');
    $table->morphs('bookerable');
    $table->dateTime('start_time');
    $table->dateTime('end_time');
    $table->string('status')->default('pending');
    $table->json('status_history')->nullable();
    $table->timestamp('status_changed_at')->nullable();
    $table->timestamps();
});
```

## Advanced Usage

### Query Scopes

```php
// Get bookings longer than 2 hours
Booking::durationLongerThan(120)->get();
// Get bookings between 1 and 2 hours
Booking::durationBetween(60, 120)->get();
// Combine with other conditions
Booking::durationLongerThan(120)->where('status', 'confirmed')->get();
```

### Status Management

```php
$booking->changeStatus('confirmed', 'Approved by supervisor', ['approver_id' => 123]);
$status = $booking->getCurrentStatus();
$history = $booking->getStatusHistory();
if ($booking->hasStatus('confirmed')) { /* ... */ }
```

### Overlap & Custom Rules

- Prevents overlapping bookings by default.
- Supports minimum interval and max duration.
- Add custom rules by implementing `OverlapRule` and registering in config.
- Example: BusinessHoursRule restricts bookings to business hours.

### Events

Events are fired for all major actions:
- BookingCreated
- BookingUpdated
- BookingDeleted
- BookingStatusChanged

### Testing & Factories

Use provided factories for testing:

```php
$booking = Booking::factory()->pending()->create();
$room = Room::factory()->create();
$user = User::factory()->create();
```

## Updating PHPDoc

To update PHPDoc for models, run:

```bash
./update-phpdoc.sh
```

## Contributing

Thank you for considering contributing to the Laravel Booking package! Please feel free to submit pull requests or create issues for bugs and feature requests.

## License

The Laravel Booking package is open-sourced software licensed under the [MIT license](LICENSE).

