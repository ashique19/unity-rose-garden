<?php

namespace Tests\Unit;

use App\Support\WaterShareCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WaterShareCalculatorTest extends TestCase
{
    #[Test]
    public function it_splits_equally_among_eighteen_flats(): void
    {
        $this->assertSame('100.00', WaterShareCalculator::share(1800, 18));
    }

    #[Test]
    public function it_splits_among_seventeen_when_one_disabled(): void
    {
        $this->assertSame('100.00', WaterShareCalculator::share(1700, 17));
    }

    #[Test]
    public function it_rejects_zero_enabled_flats(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WaterShareCalculator::share(1000, 0, 'WASA');
    }
}
