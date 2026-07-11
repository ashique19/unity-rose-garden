<?php

namespace Tests\Feature;

use App\Models\Flat;
use App\Models\GasMeterReading;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GeminiMeterAssistTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function assist_page_loads(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $flat = Flat::query()->where('name', '2A')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.gas-readings.assist', ['flat' => $flat, 'month' => '2026-07']))
            ->assertOk()
            ->assertSee('Meter photo assist')
            ->assertSee('Confirm & save');
    }

    #[Test]
    public function suggest_stores_photo_and_suggestion_without_overwriting_manual_confirm_flow(): void
    {
        $this->seed(DatabaseSeeder::class);
        config(['services.gemini.api_key' => 'test-key']);
        Storage::fake('public');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => '51.25']],
                    ],
                ]],
            ], 200),
        ]);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $flat = Flat::query()->where('name', '2A')->firstOrFail();

        GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->delete();

        $file = UploadedFile::fake()->image('meter.jpg');

        $this->actingAs($user)
            ->post(route('admin.gas-readings.suggest', $flat), [
                'bill_month' => '2026-07',
                'photo' => $file,
            ])
            ->assertRedirect(route('admin.gas-readings.assist', ['flat' => $flat, 'month' => '2026-07']))
            ->assertSessionHas('gemini_suggestion', 51.25);

        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $this->assertNotNull($reading->photo_path);
        $this->assertEquals(51.25, (float) $reading->gemini_suggestion);
        // Draft current equals previous until admin confirms a different value.
        $this->assertEquals((float) $reading->previous_m3, (float) $reading->confirmed_m3);
    }

    #[Test]
    public function suggest_on_existing_reading_updates_suggestion_not_confirmed_value(): void
    {
        $this->seed(DatabaseSeeder::class);
        config(['services.gemini.api_key' => 'test-key']);
        Storage::fake('public');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => '47.10']],
                    ],
                ]],
            ], 200),
        ]);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $flat = Flat::query()->where('name', '2A')->firstOrFail();
        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-06-01')
            ->firstOrFail();

        $originalConfirmed = (float) $reading->confirmed_m3;

        $this->actingAs($user)
            ->post(route('admin.gas-readings.suggest', $flat), [
                'bill_month' => '2026-06',
                'photo' => UploadedFile::fake()->image('meter.jpg'),
            ])
            ->assertRedirect();

        $reading->refresh();
        $this->assertEquals(47.10, (float) $reading->gemini_suggestion);
        $this->assertEquals($originalConfirmed, (float) $reading->confirmed_m3);
        $this->assertEquals($originalConfirmed, (float) $reading->current_m3);
        $this->assertNotNull($reading->photo_path);
    }

    #[Test]
    public function confirm_save_writes_admin_value_not_forced_suggestion(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $flat = Flat::query()->where('name', '2A')->firstOrFail();

        GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->delete();

        $this->actingAs($user)
            ->post(route('admin.gas-readings.store'), [
                'flat_id' => $flat->id,
                'bill_month' => '2026-07',
                'reading_date' => '2026-07-31',
                'previous_m3' => 46.28,
                'current_m3' => 50.00,
                'gemini_suggestion' => 51.25,
            ])
            ->assertRedirect();

        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $this->assertEquals(50.00, (float) $reading->confirmed_m3);
        $this->assertEquals(51.25, (float) $reading->gemini_suggestion);
    }
}
