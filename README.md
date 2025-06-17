# Laravel Booking

A flexible and powerful booking system for Laravel applications that supports polymorphic relationships and time slot management.

## Features

- 🕒 Time slot management with overlap prevention
- 🔄 Polymorphic relationships for bookable resources
- ⚙️ Configurable validation rules
- 🎯 Custom booking rules support
- 📅 Business hours validation
- 🔒 Booking duration limits
- 🎨 Easy to extend and customize
- 📊 Status tracking with history
- 🔄 Event-driven architecture
- 🛡️ Built-in validation rules
- 🧪 Comprehensive testing support
- 🔍 Advanced querying capabilities
- 💾 Multi-database support (MySQL, PostgreSQL, SQLite, SQL Server)
- ⏱️ Flexible duration calculations
- 🔄 Status history tracking
- 🎯 Configurable overlap rules

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
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

This will create a `config/booking.php` file in your config directory.

## Configuration

The package is highly configurable through the `config/booking.php` file:

```php
return [
    // Define allowed booking statuses
    'statuses' => [
        'pending',
        'confirmed',
        'cancelled'
    ],

    // Configure overlap rules
    'overlap' => [
        'enabled' => true,
        'allow_same_booker' => false,
        'min_time_between' => 30, // minutes
        'max_duration' => 120, // minutes
        'rules' => [
            'business_hours' => [
                'enabled' => true,
                'class' => \CheeasyTech\Booking\Rules\BusinessHoursRule::class,
                'config' => [
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                    'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
                ]
            ]
        ]
    ]
];
```

## Quick Start

```php
use CheeasyTech\Booking\Models\Booking;
use Carbon\Carbon;

// Setup your models with the traits and interfaces
class Room extends Model implements Bookable { /* ... */ }
class User extends Model implements Bookerable { /* ... */ }

// Create a booking
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

// Check availability
$isAvailable = !$booking->hasOverlap(
    Carbon::tomorrow()->setHour(10),
    Carbon::tomorrow()->setHour(11)
);
```

## Database Schema

The package creates the following database table:

```php
Schema::create('bookings', function (Blueprint $table) {
    $table->id();
    $table->morphs('bookable');    // For the resource being booked (e.g., room, service)
    $table->morphs('bookerable');  // For the entity making the booking (e.g., user, organization)
    $table->dateTime('start_time');
    $table->dateTime('end_time');
    $table->string('status')->nullable();
    $table->json('status_history')->nullable();
    $table->timestamp('status_changed_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

## Advanced Usage

### Query Scopes

The package provides powerful query scopes for duration-based filtering that work across different database systems:

```php
// Get bookings longer than 2 hours
$longBookings = Booking::durationLongerThan(120)->get();

// Get short bookings (less than 30 minutes)
$shortBookings = Booking::durationShorterThan(30)->get();

// Get bookings with exact duration
$oneHourBookings = Booking::durationEquals(60)->get();

// Get bookings between 1 and 2 hours
$mediumBookings = Booking::durationBetween(60, 120)->get();

// Combine with other conditions
$confirmedLongBookings = Booking::durationLongerThan(120)
    ->where('status', 'confirmed')
    ->get();
```

### Status Management

The package includes a robust status management system:

```php
// Change status with metadata
$booking->changeStatus(
    'confirmed',
    'Approved by supervisor',
    ['approver_id' => 123]
);

// Get current status
$status = $booking->getCurrentStatus();
echo $status->getStatus();        // 'confirmed'
echo $status->getReason();        // 'Approved by supervisor'
echo $status->getMetadata();      // ['approver_id' => 123]

// Get status history
$history = $booking->getStatusHistory();
foreach ($history as $status) {
    echo $status->getStatus();
    echo $status->getChangedAt()->format('Y-m-d H:i:s');
}

// Check current status
if ($booking->hasStatus('confirmed')) {
    // Do something
}
```

### Event Handling

The package fires events for all major actions:

```php
use CheeasyTech\Booking\Events\BookingCreated;
use CheeasyTech\Booking\Events\BookingUpdated;
use CheeasyTech\Booking\Events\BookingDeleted;
use CheeasyTech\Booking\Events\BookingStatusChanged;

class BookingEventSubscriber
{
    public function handleBookingCreated(BookingCreated $event)
    {
        $booking = $event->booking;
        // Handle booking creation
    }

    public function handleBookingUpdated(BookingUpdated $event)
    {
        $booking = $event->booking;
        // Handle booking update
    }

    public function handleBookingDeleted(BookingDeleted $event)
    {
        $booking = $event->booking;
        // Handle booking deletion
    }

    public function handleBookingStatusChanged(BookingStatusChanged $event)
    {
        $booking = $event->booking;
        $newStatus = $event->newStatus;
        // Handle status change
    }

    public function subscribe($events)
    {
        $events->listen(BookingCreated::class, [self::class, 'handleBookingCreated']);
        $events->listen(BookingUpdated::class, [self::class, 'handleBookingUpdated']);
        $events->listen(BookingDeleted::class, [self::class, 'handleBookingDeleted']);
        $events->listen(BookingStatusChanged::class, [self::class, 'handleBookingStatusChanged']);
    }
}
```

### Testing

The package includes comprehensive test helpers and factories:

```php
use CheeasyTech\Booking\Tests\TestCase;

class BookingTest extends TestCase
{
    /** @test */
    public function it_manages_booking_status()
    {
        $booking = Booking::factory()->create(['status' => 'pending']);

        $booking->changeStatus('confirmed', 'Approved by admin');
        
        $this->assertEquals('confirmed', $booking->status);
        $this->assertCount(1, $booking->getStatusHistory());
    }

    /** @test */
    public function it_prevents_overlapping_bookings()
    {
        $room = Room::factory()->create();
        $user = User::factory()->create();

        // Create first booking
        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:00:00',
                'end_time' => '2024-01-01 11:00:00',
                'status' => 'confirmed'
            ]);

        // Attempt to create overlapping booking
        $this->expectException(\Exception::class);
        
        Booking::factory()
            ->for($room, 'bookable')
            ->for($user, 'bookerable')
            ->create([
                'start_time' => '2024-01-01 10:30:00',
                'end_time' => '2024-01-01 11:30:00',
                'status' => 'pending'
            ]);
    }
}
```

## Contributing

Thank you for considering contributing to the Laravel Booking package! Please feel free to submit pull requests or create issues for bugs and feature requests.

## License

The Laravel Booking package is open-sourced software licensed under the [MIT license](LICENSE).

