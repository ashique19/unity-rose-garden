<?php

namespace Tests\Feature;

use App\Models\Flat;
use App\Models\GasMeterReading;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    #[Test]
    public function building_print_embeds_gas_meter_photo_thumb(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        $flat = Flat::query()->where('name', '2A')->firstOrFail();
        $path = UploadedFile::fake()->image('meter.jpg', 640, 480)
            ->store('meter-readings/'.$flat->id, 'public');

        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-06-01')
            ->firstOrFail();
        $reading->update(['photo_path' => $path]);

        $this->get(route('public.statements.print-building', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Meter photo')
            ->assertSee('data:image/jpeg;base64,', false)
            ->assertSee('class="gas-photo"', false);
    }

    #[Test]
    public function flat_print_embeds_gas_meter_photo_thumb(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        $flat = Flat::query()->where('name', '2A')->firstOrFail();
        $path = UploadedFile::fake()->image('meter.jpg', 400, 300)
            ->store('meter-readings/'.$flat->id, 'public');

        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-06-01')
            ->firstOrFail();
        $reading->update(['photo_path' => $path]);

        $this->get(route('public.statements.print', ['flat' => $flat, 'month' => '2026-06']))
            ->assertOk()
            ->assertSee('Meter photo')
            ->assertSee('data:image/jpeg;base64,', false);
    }
}
