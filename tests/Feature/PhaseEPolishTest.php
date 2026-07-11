<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Flat;
use App\Models\User;
use App\Support\Auditor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhaseEPolishTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function statement_print_view_returns_ok(): void
    {
        $this->seed(DatabaseSeeder::class);

        $flat = Flat::query()->where('name', '2A')->firstOrFail();

        $this->get(route('public.statements.print', [
            'flat' => $flat,
            'month' => '2026-06',
        ]))
            ->assertOk()
            ->assertSee('Unity Rose Garden')
            ->assertSee('Flat 2A')
            ->assertSee('Print / Save as PDF');
    }

    #[Test]
    public function charge_templates_require_secretary_or_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('charge-templates.index'))->assertRedirect(route('login'));

        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->get(route('charge-templates.index'))
            ->assertOk();
    }

    #[Test]
    public function legacy_bill_history_is_admin_only(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('bill-history'))->assertRedirect(route('login'));
    }

    #[Test]
    public function auditor_writes_log_entries(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user);
        Auditor::log('test.action', $user, ['ok' => true]);

        $this->assertTrue(
            AuditLog::query()->where('action', 'test.action')->where('user_id', $user->id)->exists()
        );
    }

    #[Test]
    public function admin_can_view_audit_log(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('Audit log');
    }
}
