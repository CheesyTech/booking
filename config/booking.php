<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Booking Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the booking system settings
    |
    */

    // Available booking statuses
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

    // Overlap validation settings
    'overlap' => [
        // Enable/disable overlap checking
        'enabled' => true,

        // Allow overlapping for same booker
        'allow_same_booker' => false,

        // Minimum time between bookings in minutes
        'min_time_between' => 0,

        // Maximum booking duration in minutes (0 = unlimited)
        'max_duration' => 0,

        // Custom validation rules
        'rules' => [
            // Example: Prevent bookings outside business hours
            'business_hours' => [
                'enabled' => false,
                'class' => \CheeasyTech\Booking\Rules\BusinessHoursRule::class,
            ],
        ],
    ],

    // Events
    'events' => [
        // Enable/disable events
        'enabled' => true,

        // Event classes
        'classes' => [
            'created' => \CheeasyTech\Booking\Events\BookingCreated::class,
            'updated' => \CheeasyTech\Booking\Events\BookingUpdated::class,
            'deleted' => \CheeasyTech\Booking\Events\BookingDeleted::class,
            'status_changed' => \CheeasyTech\Booking\Events\BookingStatusChanged::class,
        ],
    ],
];
