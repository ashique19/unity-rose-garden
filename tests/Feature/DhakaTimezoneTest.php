<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DhakaTimezoneTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function application_uses_asia_dhaka_timezone(): void
    {
        $this->assertSame('Asia/Dhaka', config('app.timezone'));
        $this->assertSame('Asia/Dhaka', config('app.schedule_timezone'));
        $this->assertSame('Asia/Dhaka', date_default_timezone_get());
        $this->assertSame('Asia/Dhaka', now()->timezoneName);
        $this->assertSame('Asia/Dhaka', Carbon::now()->timezoneName);
    }

    #[Test]
    public function created_at_timestamps_use_dhaka_wall_clock(): void
    {
        $this->seed(DatabaseSeeder::class);

        Carbon::setTestNow(Carbon::create(2026, 7, 11, 1, 30, 0, 'Asia/Dhaka'));

        $user = User::query()->create([
            'name' => 'Timezone Tester',
            'phone' => '01700000099',
            'password' => 'password',
        ]);

        $this->assertSame(
            '2026-07-11 01:30:00',
            $user->created_at->timezone('Asia/Dhaka')->format('Y-m-d H:i:s')
        );

        Carbon::setTestNow();
    }

    #[Test]
    public function audit_log_date_search_uses_dhaka_day_bounds(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();

        Carbon::setTestNow(Carbon::create(2026, 7, 11, 23, 45, 0, 'Asia/Dhaka'));
        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'timezone.check',
            'subject_type' => null,
            'subject_id' => null,
            'meta' => null,
            'ip_address' => '127.0.0.1',
        ]);
        Carbon::setTestNow();

        $this->actingAs($admin)
            ->get(route('admin.audit.index', [
                'from' => '2026-07-11',
                'to' => '2026-07-11',
            ]))
            ->assertOk()
            ->assertSee('timezone.check');

        $this->actingAs($admin)
            ->get(route('admin.audit.index', [
                'from' => '2026-07-12',
                'to' => '2026-07-12',
            ]))
            ->assertOk()
            ->assertDontSee('timezone.check');
    }

    #[Test]
    public function log_channel_writes_without_error_under_dhaka_timezone(): void
    {
        Log::info('dhaka-timezone-smoke');
        $this->assertTrue(true);
    }
}
