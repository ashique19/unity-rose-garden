<?php

namespace Tests\Feature;

use App\Models\AccountLedgerEntry;
use App\Models\BillType;
use App\Models\Building;
use App\Models\Collection;
use App\Models\CommonMeterReading;
use App\Models\Flat;
use App\Models\FlatBillTypeSetting;
use App\Models\MonthlyStatement;
use App\Models\User;
use App\Services\MonthStatementGenerator;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhaseCAccountsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function wasa_splits_across_enabled_flats_on_generate(): void
    {
        $this->seed(DatabaseSeeder::class);

        CommonMeterReading::query()->create([
            'meter_key' => 'wasa',
            'bill_month' => '2026-07-01',
            'total_amount' => 1800,
            'reading_date' => '2026-07-31',
        ]);

        $stats = app(MonthStatementGenerator::class)->generate('2026-07-01', 150);

        $this->assertSame(18, $stats['water_lines']);

        $flat = Flat::query()->where('name', '2A')->firstOrFail();
        $statement = MonthlyStatement::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $wasa = $statement->lines->firstWhere('bill_type_key', 'wasa');
        $this->assertNotNull($wasa);
        $this->assertEquals(100.0, (float) $wasa->amount);
        $this->assertSame('WASA – Jul 2026', $wasa->label);
    }

    #[Test]
    public function deep_tubewell_and_wasa_both_apply_with_independent_omit(): void
    {
        $this->seed(DatabaseSeeder::class);

        $deepType = BillType::query()->where('key', 'deep_tubewell')->firstOrFail();
        $flat3a = Flat::query()->where('name', '3A')->firstOrFail();

        FlatBillTypeSetting::query()->updateOrCreate(
            ['flat_id' => $flat3a->id, 'bill_type_id' => $deepType->id],
            ['enabled' => false]
        );

        CommonMeterReading::query()->create([
            'meter_key' => 'deep_tubewell',
            'bill_month' => '2026-07-01',
            'total_amount' => 1700,
        ]);
        CommonMeterReading::query()->create([
            'meter_key' => 'wasa',
            'bill_month' => '2026-07-01',
            'total_amount' => 1800,
        ]);

        $stats = app(MonthStatementGenerator::class)->generate('2026-07-01', 150);

        // 17 deep + 18 wasa
        $this->assertSame(35, $stats['water_lines']);

        $flat2a = Flat::query()->where('name', '2A')->firstOrFail();
        $statement2a = MonthlyStatement::query()
            ->where('flat_id', $flat2a->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $this->assertEquals(100.0, (float) $statement2a->lines->firstWhere('bill_type_key', 'deep_tubewell')->amount);
        $this->assertEquals(100.0, (float) $statement2a->lines->firstWhere('bill_type_key', 'wasa')->amount);

        $statement3a = MonthlyStatement::query()
            ->where('flat_id', $flat3a->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();
        $this->assertNull($statement3a->lines->firstWhere('bill_type_key', 'deep_tubewell'));
        $this->assertEquals(100.0, (float) $statement3a->lines->firstWhere('bill_type_key', 'wasa')->amount);
    }

    #[Test]
    public function common_water_bill_excludes_disabled_flats_from_divisor(): void
    {
        $this->seed(DatabaseSeeder::class);

        $wasaType = BillType::query()->where('key', 'wasa')->firstOrFail();
        $flat3a = Flat::query()->where('name', '3A')->firstOrFail();

        FlatBillTypeSetting::query()->updateOrCreate(
            ['flat_id' => $flat3a->id, 'bill_type_id' => $wasaType->id],
            ['enabled' => false]
        );

        CommonMeterReading::query()->create([
            'meter_key' => 'wasa',
            'bill_month' => '2026-07-01',
            'total_amount' => 1700,
        ]);

        app(MonthStatementGenerator::class)->generate('2026-07-01', 150);

        $enabled = Flat::query()->with('billTypeSettings.billType')->get()
            ->filter(fn (Flat $f) => $f->isBillTypeEnabled('wasa'));
        $this->assertCount(17, $enabled);

        $flat2a = Flat::query()->where('name', '2A')->firstOrFail();
        $statement = MonthlyStatement::query()
            ->where('flat_id', $flat2a->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $this->assertEquals(100.0, (float) $statement->lines->firstWhere('bill_type_key', 'wasa')->amount);

        $statement3a = MonthlyStatement::query()
            ->where('flat_id', $flat3a->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();
        $this->assertNull($statement3a->lines->firstWhere('bill_type_key', 'wasa'));
    }

    #[Test]
    public function common_water_bill_blocks_when_no_enabled_flats(): void
    {
        $this->seed(DatabaseSeeder::class);

        $wasaType = BillType::query()->where('key', 'wasa')->firstOrFail();
        FlatBillTypeSetting::query()
            ->where('bill_type_id', $wasaType->id)
            ->update(['enabled' => false]);

        CommonMeterReading::query()->create([
            'meter_key' => 'wasa',
            'bill_month' => '2026-07-01',
            'total_amount' => 1000,
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(MonthStatementGenerator::class)->generate('2026-07-01', 150);
    }

    #[Test]
    public function water_bills_admin_page_shows_both_types(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.water.index', ['month' => '2026-07']))
            ->assertOk()
            ->assertSee('Deep tube-well')
            ->assertSee('WASA')
            ->assertSee('Omit flats')
            ->assertSee('Plain monthly bill')
            ->assertSee('Billing month');
    }

    #[Test]
    public function deep_tubewell_save_ignores_meter_readings(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.water.store'), [
                'meter_key' => 'deep_tubewell',
                'bill_month' => '2026-07',
                'total_amount' => 900,
                'previous_reading' => 10,
                'current_reading' => 20,
                'reading_date' => '2026-07-15',
                'note' => 'July deep tube-well',
            ])
            ->assertRedirect(route('admin.water.index', ['month' => '2026-07']));

        $row = CommonMeterReading::query()
            ->where('meter_key', 'deep_tubewell')
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $this->assertEquals(900, (float) $row->total_amount);
        $this->assertNull($row->previous_reading);
        $this->assertNull($row->current_reading);
        $this->assertNull($row->reading_date);
        $this->assertSame('July deep tube-well', $row->note);
    }

    #[Test]
    public function collection_posts_to_ledger_and_updates_pending(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $flat = Flat::query()->where('name', '2A')->firstOrFail();
        $statement = MonthlyStatement::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-06-01')
            ->firstOrFail();

        $pendingBefore = (float) $statement->pendingAmount();
        $this->assertGreaterThan(0, $pendingBefore);

        $this->actingAs($user)
            ->post(route('admin.collections.store'), [
                'monthly_statement_id' => $statement->id,
                'amount' => 100,
                'collected_on' => '2026-07-10',
                'note' => 'Partial',
                'post_to_ledger' => '1',
            ])
            ->assertRedirect();

        $statement->refresh()->load('collections');
        $this->assertEqualsWithDelta($pendingBefore - 100, (float) $statement->pendingAmount(), 0.01);

        $this->assertTrue(
            AccountLedgerEntry::query()
                ->where('type', 'cash_in')
                ->where('amount', 100)
                ->where('flat_id', $flat->id)
                ->exists()
        );

        $collection = Collection::query()
            ->where('monthly_statement_id', $statement->id)
            ->where('amount', 100)
            ->firstOrFail();

        $building = Building::query()->firstOrFail();
        $this->assertNotNull($collection->balance_before);
        $this->assertNotNull($collection->balance_after);
        $this->assertEqualsWithDelta(
            (float) $collection->balance_before + 100,
            (float) $collection->balance_after,
            0.01
        );
        $this->assertEqualsWithDelta(
            (float) $collection->balance_after,
            $building->fresh()->balanceAmount(),
            0.01
        );
    }

    #[Test]
    public function ledger_cash_in_without_flat_and_balance(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $building = Building::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.ledger.store'), [
                'type' => 'cash_in',
                'amount' => 500,
                'entry_date' => '2026-07-01',
                'category' => 'donation',
                'note' => 'Anonymous donation',
            ])
            ->assertRedirect();

        $repairHead = \App\Models\ExpenseHead::query()->where('key', 'repair')->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.ledger.store'), [
                'type' => 'cash_out',
                'amount' => 200,
                'entry_date' => '2026-07-02',
                'expense_head_id' => $repairHead->id,
                'note' => 'Pump repair',
            ])
            ->assertRedirect();

        $this->assertEquals('300.00', $building->fresh()->balance());
    }

    #[Test]
    public function accounts_dashboard_loads_for_admin(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Pending collections')
            ->assertDontSee('Total cash in')
            ->assertDontSee('Total cash out')
            ->assertSee('Receive');
    }

    #[Test]
    public function dashboard_receive_records_pending_collection_and_returns(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $statement = MonthlyStatement::query()
            ->with(['flat', 'lines', 'collections'])
            ->get()
            ->first(fn (MonthlyStatement $s) => (float) $s->pendingAmount() > 0);

        $this->assertNotNull($statement);

        $pending = (float) $statement->pendingAmount();
        $flatName = $statement->flat?->name;

        $this->actingAs($user)
            ->post(route('admin.collections.store'), [
                'monthly_statement_id' => $statement->id,
                'amount' => $pending,
                'collected_on' => now()->toDateString(),
                'post_to_ledger' => '1',
                'redirect_to' => 'dashboard',
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        $this->assertEquals(0.0, (float) $statement->fresh()->load(['lines', 'collections'])->pendingAmount());

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Collection recorded for '.$flatName);
    }

    #[Test]
    public function collections_index_defaults_to_latest_statement_month_with_flats(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.collections.index'))
            ->assertOk()
            ->assertSee('value="2026-06"', false)
            ->assertSee('2A')
            ->assertSee('10B');
    }
}
