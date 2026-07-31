<?php

namespace Tests\Unit;

use App\Support\BillMonth;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillMonthTest extends TestCase
{
    #[Test]
    public function parse_does_not_overflow_short_months_on_day_31(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31, 15, 30, 0, 'Asia/Dhaka'));

        $june = BillMonth::parse('2026-06');

        $this->assertSame('2026-06-01', $june->toDateString());
        $this->assertSame('2026-06', $june->format('Y-m'));
        $this->assertSame('June 2026', BillMonth::label('2026-06'));

        Carbon::setTestNow();
    }

    #[Test]
    #[DataProvider('shortMonthsProvider')]
    public function parse_keeps_short_months_on_day_31(string $yearMonth, string $expected): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31, 12, 0, 0, 'Asia/Dhaka'));

        $this->assertSame($expected, BillMonth::parse($yearMonth)->toDateString());

        Carbon::setTestNow();
    }

    public static function shortMonthsProvider(): array
    {
        return [
            'february' => ['2026-02', '2026-02-01'],
            'april' => ['2026-04', '2026-04-01'],
            'june' => ['2026-06', '2026-06-01'],
            'september' => ['2026-09', '2026-09-01'],
            'november' => ['2026-11', '2026-11-01'],
            'may stays may' => ['2026-05', '2026-05-01'],
            'july stays july' => ['2026-07', '2026-07-01'],
        ];
    }

    #[Test]
    public function parse_accepts_full_date_and_defaults_to_current_month(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31, 12, 0, 0, 'Asia/Dhaka'));

        $this->assertSame('2026-06-01', BillMonth::parse('2026-06-15')->toDateString());
        $this->assertSame('2026-07-01', BillMonth::parse(null)->toDateString());

        Carbon::setTestNow();
    }

    #[Test]
    public function unsafe_carbon_y_m_still_overflows_proving_the_bug(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31, 12, 0, 0, 'Asia/Dhaka'));

        $unsafe = Carbon::createFromFormat('Y-m', '2026-06')->startOfMonth();
        $this->assertSame('2026-07-01', $unsafe->toDateString());

        Carbon::setTestNow();
    }
}
