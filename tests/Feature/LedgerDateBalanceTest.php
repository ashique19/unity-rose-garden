<?php

namespace Tests\Feature;

use App\Models\AccountLedgerEntry;
use App\Models\Building;
use App\Models\ExpenseHead;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LedgerDateBalanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ledger_can_be_filtered_by_date_range(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();

        AccountLedgerEntry::query()->create([
            'type' => AccountLedgerEntry::TYPE_CASH_IN,
            'amount' => 100,
            'entry_date' => '2026-06-10',
            'note' => 'June donation',
        ]);
        AccountLedgerEntry::query()->create([
            'type' => AccountLedgerEntry::TYPE_CASH_IN,
            'amount' => 200,
            'entry_date' => '2026-07-15',
            'note' => 'July donation',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.ledger.index', [
                'from' => '2026-07-01',
                'to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertSee('From date')
            ->assertSee('To date')
            ->assertSee('July donation')
            ->assertDontSee('June donation');
    }

    #[Test]
    public function ledger_shows_balance_before_and_after_for_each_entry(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();
        $building = Building::query()->firstOrFail();
        $building->update(['opening_balance' => 1000]);

        // Clear seeded ledger noise for a clean running balance check.
        AccountLedgerEntry::query()->delete();

        $head = ExpenseHead::query()->where('key', 'supplies')->firstOrFail();

        $cashIn = AccountLedgerEntry::query()->create([
            'type' => AccountLedgerEntry::TYPE_CASH_IN,
            'amount' => 500,
            'entry_date' => '2026-08-01',
            'note' => 'Opening top-up',
        ]);

        $cashOut = AccountLedgerEntry::query()->create([
            'type' => AccountLedgerEntry::TYPE_CASH_OUT,
            'amount' => 200,
            'entry_date' => '2026-08-02',
            'expense_head_id' => $head->id,
            'category' => $head->label,
            'note' => 'Bought supplies',
        ]);

        $html = $this->actingAs($admin)
            ->get(route('admin.ledger.index'))
            ->assertOk()
            ->assertSee('Balance before')
            ->assertSee('Balance after')
            ->getContent();

        // Newest first: cash out then cash in.
        // Cash out: before 1500, after 1300
        // Cash in: before 1000, after 1500
        $this->assertStringContainsString('Bought supplies', $html);
        $this->assertStringContainsString('Opening top-up', $html);
        $this->assertStringContainsString('1,500.00', $html);
        $this->assertStringContainsString('1,300.00', $html);
        $this->assertStringContainsString('1,000.00', $html);

        $this->assertEqualsWithDelta(1300.0, $building->fresh()->balanceAmount(), 0.01);

        $balances = app(\App\Services\LedgerRunningBalance::class)
            ->forEntries(collect([$cashIn, $cashOut]), $building->fresh());

        $this->assertEqualsWithDelta(1000.0, $balances[$cashIn->id]['before'], 0.01);
        $this->assertEqualsWithDelta(1500.0, $balances[$cashIn->id]['after'], 0.01);
        $this->assertEqualsWithDelta(1500.0, $balances[$cashOut->id]['before'], 0.01);
        $this->assertEqualsWithDelta(1300.0, $balances[$cashOut->id]['after'], 0.01);
    }

    #[Test]
    public function filtered_ledger_balances_still_use_full_history(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();
        $building = Building::query()->firstOrFail();
        $building->update(['opening_balance' => 0]);
        AccountLedgerEntry::query()->delete();

        AccountLedgerEntry::query()->create([
            'type' => AccountLedgerEntry::TYPE_CASH_IN,
            'amount' => 400,
            'entry_date' => '2026-06-01',
            'note' => 'Prior month',
        ]);

        $july = AccountLedgerEntry::query()->create([
            'type' => AccountLedgerEntry::TYPE_CASH_OUT,
            'amount' => 50,
            'entry_date' => '2026-07-10',
            'note' => 'July spend',
            'category' => 'Misc',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.ledger.index', [
                'from' => '2026-07-01',
                'to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertSee('July spend')
            ->assertDontSee('Prior month')
            ->assertSee('400.00')
            ->assertSee('350.00');

        $balances = app(\App\Services\LedgerRunningBalance::class)
            ->forEntries(collect([$july]), $building->fresh());

        $this->assertEqualsWithDelta(400.0, $balances[$july->id]['before'], 0.01);
        $this->assertEqualsWithDelta(350.0, $balances[$july->id]['after'], 0.01);
    }
}
