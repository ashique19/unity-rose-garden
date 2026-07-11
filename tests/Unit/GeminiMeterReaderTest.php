<?php

namespace Tests\Unit;

use App\Services\GeminiMeterReader;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GeminiMeterReaderTest extends TestCase
{
    #[Test]
    public function it_extracts_number_from_model_text(): void
    {
        $reader = new GeminiMeterReader;

        $this->assertSame(46.28, $reader->extractNumber('46.28'));
        $this->assertSame(103.84, $reader->extractNumber('The reading is 103.84 m3'));
        $this->assertSame(9.72, $reader->extractNumber("9.72\n"));
        $this->assertNull($reader->extractNumber('unclear'));
    }
}
