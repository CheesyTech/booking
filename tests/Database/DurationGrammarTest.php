<?php

namespace CheeasyTech\Booking\Tests\Database;

use CheeasyTech\Booking\Database\DurationGrammar;
use CheeasyTech\Booking\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class DurationGrammarTest extends TestCase
{
    /** @test */
    public function it_generates_correct_sql_for_sqlite()
    {
        DB::shouldReceive('getDriverName')->once()->andReturn('sqlite');

        $sql = DurationGrammar::getDurationExpression('start_time', 'end_time');

        $this->assertEquals(
            "(strftime('%s', end_time) - strftime('%s', start_time)) / 60",
            $sql
        );
    }

    /** @test */
    public function it_generates_correct_sql_for_mysql()
    {
        DB::shouldReceive('getDriverName')->once()->andReturn('mysql');

        $sql = DurationGrammar::getDurationExpression('start_time', 'end_time');

        $this->assertEquals(
            'TIMESTAMPDIFF(MINUTE, start_time, end_time)',
            $sql
        );
    }

    /** @test */
    public function it_generates_correct_sql_for_pgsql()
    {
        DB::shouldReceive('getDriverName')->once()->andReturn('pgsql');

        $sql = DurationGrammar::getDurationExpression('start_time', 'end_time');

        $this->assertEquals(
            'EXTRACT(EPOCH FROM (end_time - start_time)) / 60',
            $sql
        );
    }

    /** @test */
    public function it_generates_correct_sql_for_sqlsrv()
    {
        DB::shouldReceive('getDriverName')->once()->andReturn('sqlsrv');

        $sql = DurationGrammar::getDurationExpression('start_time', 'end_time');

        $this->assertEquals(
            'DATEDIFF(MINUTE, start_time, end_time)',
            $sql
        );
    }

    /** @test */
    public function it_throws_exception_for_unsupported_driver()
    {
        DB::shouldReceive('getDriverName')->once()->andReturn('unsupported');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported database driver: unsupported');

        DurationGrammar::getDurationExpression('start_time', 'end_time');
    }

    /** @test */
    public function it_handles_custom_column_names()
    {
        DB::shouldReceive('getDriverName')->once()->andReturn('mysql');

        $sql = DurationGrammar::getDurationExpression('custom_start', 'custom_end');

        $this->assertEquals(
            'TIMESTAMPDIFF(MINUTE, custom_start, custom_end)',
            $sql
        );
    }
}
