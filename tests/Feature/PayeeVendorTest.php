<?php

namespace Tests\Feature;

use App\Models\AccountLedgerEntry;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PayeeVendorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function payees_page_requires_auth(): void
    {
        $this->get(route('admin.payees.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_register_edit_and_delete_payee(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.payees.store'), [
                'name' => 'WASA',
                'phone' => '01700000000',
                'note' => 'Water utility',
            ])
            ->assertRedirect(route('admin.payees.index'));

        $vendor = Vendor::query()->where('name', 'WASA')->firstOrFail();

        $this->actingAs($user)
            ->put(route('admin.payees.update', $vendor), [
                'name' => 'WASA Dhaka',
                'phone' => '01700000001',
                'note' => 'Updated',
                'sort_order' => 5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.payees.index'));

        $this->assertSame('WASA Dhaka', $vendor->fresh()->name);

        $this->actingAs($user)
            ->delete(route('admin.payees.destroy', $vendor))
            ->assertRedirect(route('admin.payees.index'));

        $this->assertDatabaseMissing('vendors', ['id' => $vendor->id]);
    }

    #[Test]
    public function ledger_payee_dropdown_uses_active_vendors(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $active = Vendor::query()->create([
            'name' => 'Deep Tube Vendor',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Vendor::query()->create([
            'name' => 'Inactive Vendor',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('admin.ledger.index'))
            ->assertOk()
            ->assertSee('Deep Tube Vendor')
            ->assertDontSee('Inactive Vendor')
            ->assertSee('Manage payees');

        $this->actingAs($user)
            ->post(route('admin.ledger.store'), [
                'type' => 'cash_in',
                'amount' => 250,
                'entry_date' => '2026-08-01',
                'vendor_id' => $active->id,
                'note' => 'With payee',
            ])
            ->assertRedirect(route('admin.ledger.index'));

        $entry = AccountLedgerEntry::query()->latest('id')->firstOrFail();
        $this->assertSame($active->id, $entry->vendor_id);
        $this->assertSame('Deep Tube Vendor', $entry->payee);
    }

    #[Test]
    public function cannot_delete_payee_in_use(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $vendor = Vendor::query()->create([
            'name' => 'In Use Vendor',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        AccountLedgerEntry::query()->create([
            'type' => 'cash_out',
            'amount' => 100,
            'entry_date' => '2026-08-01',
            'vendor_id' => $vendor->id,
            'payee' => $vendor->name,
            'note' => 'Used',
        ]);

        $this->actingAs($user)
            ->from(route('admin.payees.index'))
            ->delete(route('admin.payees.destroy', $vendor))
            ->assertRedirect(route('admin.payees.index'))
            ->assertSessionHasErrors('payee');

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id]);
    }
}
