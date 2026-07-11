<?php

namespace Tests\Feature;

use App\Models\AccountLedgerEntry;
use App\Models\Building;
use App\Models\Expense;
use App\Models\ExpenseHead;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpensePurchasesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function seeded_expense_heads_exist(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(ExpenseHead::query()->where('key', 'salary')->exists());
        $this->assertTrue(ExpenseHead::query()->where('key', 'garbage')->exists());
        $this->assertTrue(ExpenseHead::query()->where('key', 'gas_purchase')->exists());
    }

    #[Test]
    public function admin_can_create_expense_head(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.expense-heads.store'), [
                'label' => 'Security',
            ])
            ->assertRedirect(route('admin.expense-heads.index'));

        $this->assertTrue(ExpenseHead::query()->where('label', 'Security')->exists());
    }

    #[Test]
    public function cannot_delete_expense_head_in_use(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();
        $head = ExpenseHead::query()->where('key', 'supplies')->firstOrFail();

        Expense::query()->create([
            'expense_head_id' => $head->id,
            'amount' => 50,
            'entry_date' => '2026-07-01',
            'note' => 'Cleaning supplies',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.expense-heads.index'))
            ->delete(route('admin.expense-heads.destroy', $head))
            ->assertRedirect(route('admin.expense-heads.index'))
            ->assertSessionHasErrors('head');

        $this->assertTrue(ExpenseHead::query()->whereKey($head->id)->exists());
    }

    #[Test]
    public function recording_expense_requires_note_and_posts_to_ledger_by_default(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();
        $building = Building::query()->firstOrFail();
        $head = ExpenseHead::query()->where('key', 'water_bill')->firstOrFail();

        $opening = (float) $building->balance();

        $this->actingAs($admin)
            ->post(route('admin.expenses.store'), [
                'expense_head_id' => $head->id,
                'amount' => 100,
                'entry_date' => '2026-07-05',
                'payee' => 'WASA',
            ])
            ->assertSessionHasErrors('note');

        $this->actingAs($admin)
            ->post(route('admin.expenses.store'), [
                'expense_head_id' => $head->id,
                'amount' => 100,
                'entry_date' => '2026-07-05',
                'payee' => 'WASA',
                'note' => 'July water bill',
                'post_to_ledger' => '1',
                'from' => '2026-07-01',
                'to' => '2026-07-31',
            ])
            ->assertRedirect();

        $expense = Expense::query()
            ->where('expense_head_id', $head->id)
            ->where('note', 'July water bill')
            ->firstOrFail();

        $this->assertSame('WASA', $expense->payee);

        $entry = AccountLedgerEntry::query()
            ->where('expense_id', $expense->id)
            ->where('type', 'cash_out')
            ->firstOrFail();

        $this->assertSame('WASA', $entry->payee);
        $this->assertEqualsWithDelta($opening - 100, (float) $building->fresh()->balance(), 0.01);
        $this->assertEqualsWithDelta($opening, (float) $expense->balance_before, 0.01);
        $this->assertEqualsWithDelta($opening - 100, (float) $expense->balance_after, 0.01);
    }

    #[Test]
    public function expense_can_be_saved_without_posting_to_ledger(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();
        $building = Building::query()->firstOrFail();
        $head = ExpenseHead::query()->where('key', 'supplies')->firstOrFail();
        $opening = (float) $building->balance();

        $this->actingAs($admin)
            ->post(route('admin.expenses.store'), [
                'expense_head_id' => $head->id,
                'amount' => 75,
                'entry_date' => '2026-07-06',
                'note' => 'Pending receipt',
                'from' => '2026-07-01',
                'to' => '2026-07-31',
            ])
            ->assertRedirect();

        $expense = Expense::query()->where('note', 'Pending receipt')->firstOrFail();
        $this->assertNull($expense->ledgerEntry);
        $this->assertEqualsWithDelta($opening, (float) $building->fresh()->balance(), 0.01);
        $this->assertEqualsWithDelta($opening, (float) $expense->balance_before, 0.01);
        $this->assertEqualsWithDelta($opening, (float) $expense->balance_after, 0.01);
    }

    #[Test]
    public function expense_entry_screen_shows_available_balance(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.expenses.index', [
                'from' => '2026-07-01',
                'to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertSee('Available before')
            ->assertSee('After this expense');
    }

    #[Test]
    public function expense_print_views_load(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();
        $head = ExpenseHead::query()->where('key', 'cleaner')->firstOrFail();

        $expense = Expense::query()->create([
            'expense_head_id' => $head->id,
            'amount' => 200,
            'entry_date' => '2026-07-10',
            'payee' => 'Cleaner staff',
            'note' => 'Monthly cleaner',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.expenses.print', $expense))
            ->assertOk()
            ->assertSee('Expense / purchase voucher')
            ->assertSee('Monthly cleaner');

        $this->actingAs($admin)
            ->get(route('admin.expenses.print-list', [
                'from' => '2026-07-01',
                'to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertSee('Expense / purchase register')
            ->assertSee('Cleaner');
    }
}
