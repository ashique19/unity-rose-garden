<?php

namespace Tests\Feature;

use App\Models\BillType;
use App\Models\ChargeTemplate;
use App\Models\Flat;
use App\Models\FlatBillTypeSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlatBillTypeSettingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function settings_page_has_add_bill_type_form(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.flat-bill-type-settings.index'))
            ->assertOk()
            ->assertSee('Add bill type')
            ->assertSee(route('admin.flat-bill-type-settings.store-bill-type', absolute: false), false)
            ->assertSee('Garbage');
    }

    #[Test]
    public function admin_can_add_bill_type_enabled_for_all_flats(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.flat-bill-type-settings.store-bill-type'), [
                'label' => 'Security',
            ])
            ->assertRedirect(route('admin.flat-bill-type-settings.index'))
            ->assertSessionHas('success');

        $type = BillType::query()->where('key', 'security')->firstOrFail();
        $this->assertSame('Security', $type->label);
        $this->assertSame(BillType::NATURE_OTHER, $type->nature);
        $this->assertTrue($type->is_active);

        $this->assertSame(
            Flat::query()->count(),
            FlatBillTypeSetting::query()->where('bill_type_id', $type->id)->where('enabled', true)->count()
        );

        $this->assertFalse(ChargeTemplate::query()->where('bill_type_id', $type->id)->exists());

        $this->actingAs($admin)
            ->get(route('admin.flat-bill-type-settings.index'))
            ->assertOk()
            ->assertSee('Security');
    }

    #[Test]
    public function adding_bill_type_with_amount_creates_building_wide_template(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.flat-bill-type-settings.store-bill-type'), [
                'label' => 'Generator',
                'key' => 'generator',
                'default_amount' => '250',
            ])
            ->assertRedirect(route('admin.flat-bill-type-settings.index'));

        $type = BillType::query()->where('key', 'generator')->firstOrFail();
        $template = ChargeTemplate::query()->where('bill_type_id', $type->id)->firstOrFail();

        $this->assertSame('generator', $template->charge_key);
        $this->assertSame('Generator', $template->label);
        $this->assertEquals(250.0, (float) $template->default_amount);
        $this->assertTrue($template->is_building_wide);
    }

    #[Test]
    public function cannot_add_bill_type_with_existing_key(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.flat-bill-type-settings.index'))
            ->post(route('admin.flat-bill-type-settings.store-bill-type'), [
                'label' => 'More garbage',
                'key' => 'garbage',
            ])
            ->assertRedirect(route('admin.flat-bill-type-settings.index'))
            ->assertSessionHasErrors('key');
    }
}
