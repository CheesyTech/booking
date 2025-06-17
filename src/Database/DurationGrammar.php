<?php

namespace CheeasyTech\Booking\Database;

use Illuminate\Support\Facades\DB;

class DurationGrammar
{
    public static function getDurationExpression(string $startColumn, string $endColumn): string
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'sqlite' => "(strftime('%s', $endColumn) - strftime('%s', $startColumn)) / 60",
            'mysql' => "TIMESTAMPDIFF(MINUTE, $startColumn, $endColumn)",
            'pgsql' => "EXTRACT(EPOCH FROM ($endColumn - $startColumn)) / 60",
            'sqlsrv' => "DATEDIFF(MINUTE, $startColumn, $endColumn)",
            default => throw new \InvalidArgumentException("Unsupported database driver: $driver"),
        };
    }
}
