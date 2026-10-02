<?php

namespace Tests\Unit;

use App\Support\Duration;
use PHPUnit\Framework\TestCase;

class DurationTest extends TestCase
{
    public function test_durations_are_readable_at_a_glance(): void
    {
        $this->assertSame('0 min', Duration::human(0));
        $this->assertSame('45 min', Duration::human(45));
        $this->assertSame('1 h', Duration::human(60));
        $this->assertSame('3 h 20', Duration::human(200));
        $this->assertSame('33 h 20', Duration::human(2000));
        $this->assertSame('2 j', Duration::human(48 * 60));
        $this->assertSame('42 j 14 h', Duration::human(61376.9)); // décimal de diffInMinutes()
        $this->assertSame('0 min', Duration::human(-5));
        $this->assertSame('0 min', Duration::human(null));
    }
}
