# Laravel MultiBooking

A flexible and powerful booking system for Laravel applications that supports polymorphic relationships and time slot management.

## Features

- 🕒 Time slot management with overlap prevention
- 🔄 Polymorphic relationships for bookable resources
- ⚙️ Configurable validation rules
- 🎯 Custom booking rules support
- 📅 Business hours validation
- 🔒 Booking duration limits
- 🎨 Easy to extend and customize

## Installation

You can install the package via composer:

```bash
composer require cheesytech/laravel-multibooking
```

The package will automatically register its service provider.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="CheesyTech\LaravelMultiBooking\BookingServiceProvider"
```

This will create a `config/booking.php` file in your config directory.

## Basic Usage

### 1. Prepare Your Models

Add the `HasBookings` trait to your bookable model:

```php
use CheeasyTech\LaravelMultibooking\HasBookings;

class Room extends Model
{
    use HasBookings;
}
```

### 2. Create a Booking

```php
$room = Room::find(1);
$user = User::find(1);

$booking = new Booking();
$booking->bookable()->associate($room);
$booking->bookerable()->associate($user);
$booking->start_time = '2024-03-20 10:00:00';
$booking->end_time = '2024-03-20 11:00:00';
$booking->save();
```

### 3. Check for Overlaps

```php
$hasOverlap = $booking->hasOverlap(
    Carbon::parse('2024-03-20 10:00:00'),
    Carbon::parse('2024-03-20 11:00:00')
);

if ($hasOverlap) {
    throw new Exception('This time slot is already booked');
}
```

## Configuration Options

### Overlap Settings

```php
'overlap' => [
    'enabled' => true,
    'allow_same_booker' => false,
    'min_time_between' => 30, // minutes
    'max_duration' => 120,    // minutes
    'rules' => [
        'business_hours' => [
            'enabled' => true,
            'start' => '09:00',
            'end' => '18:00',
            'timezone' => 'UTC',
        ],
    ],
],
```

### Events

```php
'events' => [
    'enabled' => true,
    'classes' => [
        'created' => \CheeeasyTech\Booking\Events\BookingCreated::class,
        'updated' => \CheeeasyTech\Booking\Events\BookingUpdated::class,
        'deleted' => \CheeeasyTech\Booking\Events\BookingDeleted::class,
        'status_changed' => \CheeeasyTech\Booking\Events\BookingStatusChanged::class,
    ],
],
```

## Status Management

The package includes a robust status management system:

```php
// Change booking status
$booking->changeStatus('confirmed', 'Payment received', ['payment_id' => 123]);

// Get current status
$currentStatus = $booking->getCurrentStatus();

// Get status history
$statusHistory = $booking->getStatusHistory();

// Check if booking has specific status
$isConfirmed = $booking->hasStatus('confirmed');
```

### Available Statuses

```php
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
```

## Custom Rules

Create your own booking rules by implementing the `OverlapRule` interface:

```php
use CheeeasyTech\Booking\Contracts\OverlapRule;

class WeekendRule implements OverlapRule
{
    public function validate(Booking $booking, Carbon $startTime, Carbon $endTime): bool
    {
        return !$startTime->isWeekend() && !$endTime->isWeekend();
    }

    public function getErrorMessage(): string
    {
        return 'Bookings are not allowed on weekends';
    }
}
```

Register your rule in the configuration:

```php
'rules' => [
    'weekend' => [
        'enabled' => true,
    ],
],
```

## Available Methods

### Booking Model

- `bookable()`: Get the bookable resource
- `bookerable()`: Get the booking entity
- `hasOverlap()`: Check for booking overlaps
- `getDurationInMinutes()`: Get booking duration
- `validateTimeSlot()`: Validate time slot
- `changeStatus()`: Change booking status
- `getCurrentStatus()`: Get current status object
- `getStatusHistory()`: Get status history
- `hasStatus()`: Check if booking has specific status

### HasBookings Trait

- `bookings()`: Get all bookings
- `availableSlots()`: Get available time slots
- `isAvailable()`: Check if resource is available

## Events

The package fires the following events:

- `BookingCreated`: When a new booking is created
- `BookingUpdated`: When a booking is updated
- `BookingDeleted`: When a booking is deleted
- `BookingStatusChanged`: When a booking status is changed

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email security@cheesytech.com instead of using the issue tracker.

## Credits

- [CheesyTech](https://github.com/cheesytech)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

