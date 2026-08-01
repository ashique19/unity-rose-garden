<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BuildingStatementPrintTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function homepage_links_to_building_print_page(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Print building bills')
            ->assertSee(route('public.statements.print-building', ['month' => '2026-06'], absolute: false), false);
    }

    #[Test]
    public function building_print_page_lists_flat_gas_and_totals(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('public.statements.print-building', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Building-wide monthly bills')
            ->assertSee('Flat 2A')
            ->assertSee('Gas bill')
            ->assertSee('Reading (new − old)')
            ->assertSee('Used kg × rate/kg')
            ->assertSee('Other charges')
            ->assertSee('Building total')
            ->assertSee('Print / Save as PDF')
            ->assertDontSee('Flat 3A');
    }

    #[Test]
    public function building_print_defaults_to_latest_statement_month(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('public.statements.print-building'))
            ->assertOk()
            ->assertSee('June 2026');
    }
}
