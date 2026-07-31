<?php

namespace Tests\Feature;

use App\Models\Flat;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicFlatStatementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function home_lists_flats(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('2A');
        $response->assertSee('10B');
    }

    #[Test]
    public function flat_month_view_returns_ok_with_statement(): void
    {
        $this->seed(DatabaseSeeder::class);

        $flat = Flat::query()->where('name', '2A')->firstOrFail();

        $response = $this->get(route('public.flats.show', [
            'flat' => $flat,
            'month' => '2026-06',
        ]));

        $response->assertOk();
        $response->assertSee('Flat 2A');
        $response->assertSee('Gas');
    }

    #[Test]
    public function june_statement_stays_june_when_today_is_day_31(): void
    {
        $this->seed(DatabaseSeeder::class);

        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 7, 31, 18, 0, 0, 'Asia/Dhaka'));

        $flat = Flat::query()->where('name', '2A')->firstOrFail();

        $response = $this->get(route('public.flats.show', [
            'flat' => $flat,
            'month' => '2026-06',
        ]));

        $response->assertOk();
        $response->assertSee('June 2026');
        $response->assertDontSee('No statement');

        \Carbon\Carbon::setTestNow();
    }

    #[Test]
    public function flat_month_view_shows_empty_state_when_missing(): void
    {
        $this->seed(DatabaseSeeder::class);

        $flat = Flat::query()->where('name', '2A')->firstOrFail();

        $response = $this->get(route('public.flats.show', [
            'flat' => $flat,
            'month' => '2025-01',
        ]));

        $response->assertOk();
        $response->assertSee('No statement');
    }

    #[Test]
    public function offline_flats_have_gas_disabled(): void
    {
        $this->seed(DatabaseSeeder::class);

        $flat = Flat::query()->where('name', '3A')->firstOrFail();

        $this->assertFalse($flat->isBillTypeEnabled('gas'));
        $this->assertTrue($flat->isBillTypeEnabled('water'));
    }
}
