<?php

namespace Tests\Feature;

use App\Models\AccountLedgerEntry;
use App\Models\Building;
use App\Models\Collection;
use App\Models\CommonMeterReading;
use App\Models\Flat;
use App\Models\FlatBillTypeSetting;
use App\Models\BillType;
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
    public function water_splits_across_enabled_flats_on_generate(): void
    {
        $this->seed(DatabaseSeeder::class);

        CommonMeterReading::query()->create([
            'meter_key' => 'water',
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

        $water = $statement->lines->firstWhere('bill_type_key', 'water');
        $this->assertNotNull($water);
        $this->assertEquals(100.0, (float) $water->amount);
    }

    #[Test]
    public function water_excludes_disabled_flats_from_divisor(): void
    {
        $this->seed(DatabaseSeeder::class);

        $waterType = BillType::query()->where('key', 'water')->firstOrFail();
        $flat3a = Flat::query()->where('name', '3A')->firstOrFail();

        FlatBillTypeSetting::query()->updateOrCreate(
            ['flat_id' => $flat3a->id, 'bill_type_id' => $waterType->id],
            ['enabled' => false]
        );

        CommonMeterReading::query()->create([
            'meter_key' => 'water',
            'bill_month' => '2026-07-01',
            'total_amount' => 1700,
        ]);

        app(MonthStatementGenerator::class)->generate('2026-07-01', 150);

        $enabled = Flat::query()->with('billTypeSettings.billType')->get()
            ->filter(fn (Flat $f) => $f->isBillTypeEnabled('water'));
        $this->assertCount(17, $enabled);

        $flat2a = Flat::query()->where('name', '2A')->firstOrFail();
        $statement = MonthlyStatement::query()
            ->where('flat_id', $flat2a->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $this->assertEquals(100.0, (float) $statement->lines->firstWhere('bill_type_key', 'water')->amount);

        $statement3a = MonthlyStatement::query()
            ->where('flat_id', $flat3a->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();
        $this->assertNull($statement3a->lines->firstWhere('bill_type_key', 'water'));
    }

    #[Test]
    public function water_blocks_when_no_enabled_flats(): void
    {
        $this->seed(DatabaseSeeder::class);

        $waterType = BillType::query()->where('key', 'water')->firstOrFail();
        FlatBillTypeSetting::query()
            ->where('bill_type_id', $waterType->id)
            ->update(['enabled' => false]);

        CommonMeterReading::query()->create([
            'meter_key' => 'water',
            'bill_month' => '2026-07-01',
            'total_amount' => 1000,
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(MonthStatementGenerator::class)->generate('2026-07-01', 150);
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

        $this->actingAs($user)
            ->post(route('admin.ledger.store'), [
                'type' => 'cash_out',
                'amount' => 200,
                'entry_date' => '2026-07-02',
                'category' => 'maintenance',
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
            ->assertSee('Pending collections');
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
