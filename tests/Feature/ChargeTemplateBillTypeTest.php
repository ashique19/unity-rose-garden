<?php

namespace Tests\Feature;

use App\Models\BillType;
use App\Models\ChargeTemplate;
use App\Models\Flat;
use App\Models\MonthlyStatement;
use App\Models\User;
use App\Services\MonthStatementGenerator;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChargeTemplateBillTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function edit_page_exposes_bill_type_controls(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->get(route('charge-templates.index'))
            ->assertOk()
            ->assertSee('name="bill_type_id"', false)
            ->assertSee('Bill type / Charge type')
            ->assertSee('Garbage');
    }

    #[Test]
    public function building_wide_template_requires_bill_type(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $template = ChargeTemplate::query()->where('charge_key', 'garbage')->firstOrFail();

        $this->actingAs($user)
            ->put(route('charge-templates.update', $template), [
                'charge_key' => 'garbage',
                'label' => 'Garbage',
                'default_amount' => 100,
                'is_building_wide' => '1',
                'bill_type_id' => null,
            ])
            ->assertSessionHasErrors('bill_type_id');
    }

    #[Test]
    public function can_assign_bill_type_to_orphan_building_wide_template(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $garbageType = BillType::query()->where('key', 'garbage')->firstOrFail();

        $template = ChargeTemplate::query()->updateOrCreate(
            ['charge_key' => 'orphan_garbage'],
            [
                'label' => 'Orphan garbage',
                'default_amount' => 75,
                'is_building_wide' => true,
                'bill_type_id' => null,
            ]
        );

        $this->actingAs($user)
            ->put(route('charge-templates.update', $template), [
                'charge_key' => 'orphan_garbage',
                'label' => 'Orphan garbage',
                'default_amount' => 75,
                'is_building_wide' => '1',
                'bill_type_id' => $garbageType->id,
            ])
            ->assertRedirect(route('charge-templates.index'))
            ->assertSessionHas('success');

        $this->assertSame($garbageType->id, $template->fresh()->bill_type_id);
    }

    #[Test]
    public function generate_includes_garbage_when_template_has_bill_type(): void
    {
        $this->seed(DatabaseSeeder::class);

        $garbage = ChargeTemplate::query()->where('charge_key', 'garbage')->firstOrFail();
        $this->assertNotNull($garbage->bill_type_id);

        app(MonthStatementGenerator::class)->generate('2026-07-01', 150);

        $flat = Flat::query()->where('name', '2A')->firstOrFail();
        $statement = MonthlyStatement::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $line = $statement->lines->firstWhere('bill_type_key', 'garbage');
        $this->assertNotNull($line);
        $this->assertEquals((float) $garbage->default_amount, (float) $line->amount);
    }

    #[Test]
    public function generate_skips_building_wide_template_without_bill_type(): void
    {
        $this->seed(DatabaseSeeder::class);

        ChargeTemplate::query()->where('charge_key', 'garbage')->update(['bill_type_id' => null]);

        app(MonthStatementGenerator::class)->generate('2026-07-01', 150);

        $flat = Flat::query()->where('name', '2A')->firstOrFail();
        $statement = MonthlyStatement::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $this->assertNull($statement->lines->firstWhere('bill_type_key', 'garbage'));
    }
}
