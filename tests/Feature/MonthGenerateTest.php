<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Flat;
use App\Models\GasMeterReading;
use App\Models\MonthlyStatement;
use App\Models\StatementLine;
use App\Models\User;
use App\Services\MonthStatementGenerator;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonthGenerateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function generate_upserts_gas_lines_and_preserves_collections(): void
    {
        $this->seed(DatabaseSeeder::class);

        $flat = Flat::query()->where('name', '2A')->firstOrFail();
        $statement = MonthlyStatement::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-05-01')
            ->firstOrFail();

        $collectionCountBefore = Collection::query()
            ->where('monthly_statement_id', $statement->id)
            ->count();

        $this->assertGreaterThan(0, $collectionCountBefore);

        $stats = app(MonthStatementGenerator::class)->generate('2026-05-01', 146);

        $this->assertSame(
            $collectionCountBefore,
            Collection::query()->where('monthly_statement_id', $statement->id)->count()
        );

        $gasLine = StatementLine::query()
            ->where('monthly_statement_id', $statement->id)
            ->where('bill_type_key', 'gas')
            ->first();

        $this->assertNotNull($gasLine);
        $this->assertGreaterThan(0, $stats['gas_lines']);
    }

    #[Test]
    public function generate_skips_gas_for_disabled_flats(): void
    {
        $this->seed(DatabaseSeeder::class);

        $flat = Flat::query()->where('name', '3A')->firstOrFail();
        $this->assertFalse($flat->isBillTypeEnabled('gas'));

        GasMeterReading::query()->updateOrCreate(
            ['flat_id' => $flat->id, 'bill_month' => '2026-07-01'],
            [
                'reading_date' => '2026-07-31',
                'previous_m3' => 0,
                'current_m3' => 5,
                'confirmed_m3' => 5,
            ]
        );

        app(MonthStatementGenerator::class)->generate('2026-07-01', 150);

        $statement = MonthlyStatement::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->first();

        $this->assertNotNull($statement);
        $this->assertNull($statement->gasLine());
    }

    #[Test]
    public function generate_applies_building_wide_templates(): void
    {
        $this->seed(DatabaseSeeder::class);

        app(MonthStatementGenerator::class)->generate('2026-07-01', 150);

        $flat = Flat::query()->where('name', '2A')->firstOrFail();
        $statement = MonthlyStatement::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $cleaner = $statement->lines->firstWhere('bill_type_key', 'cleaner');
        $this->assertNotNull($cleaner);
        $this->assertEquals(200, (float) $cleaner->amount);

        $garbage = $statement->lines->firstWhere('bill_type_key', 'garbage');
        $this->assertNotNull($garbage);
        $this->assertEquals(100, (float) $garbage->amount);
    }

    #[Test]
    public function admin_can_open_gas_readings_page(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.gas-readings.index', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('2A')
            ->assertDontSee('>3A<', false);
    }

    #[Test]
    public function generate_page_shows_readiness_for_complete_month(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.generate.index', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Charge readiness')
            ->assertSee('All required enabled charges are entered')
            ->assertSee('15 / 15 flats')
            ->assertSee('All gas-enabled flats have readings')
            ->assertSee('building template');
    }

    #[Test]
    public function generate_page_lists_pending_gas_flats(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.generate.index', ['month' => '2026-07']))
            ->assertOk()
            ->assertSee('required item(s) still pending')
            ->assertSee('Pending:')
            ->assertSee('2A')
            ->assertDontSee('Pending: 3A', false);
    }
}
