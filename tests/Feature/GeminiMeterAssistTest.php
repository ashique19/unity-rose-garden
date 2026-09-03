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
        // Photo/OCR drafts stay unconfirmed until an admin saves the m³ value.
        $this->assertNull($reading->confirmed_m3);
        $this->assertEquals((float) $reading->previous_m3, (float) $reading->current_m3);
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

    #[Test]
    public function store_via_json_returns_reading_without_redirect(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $flat = Flat::query()->where('name', '2A')->firstOrFail();

        GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->delete();

        $this->actingAs($user)
            ->postJson(route('admin.gas-readings.store'), [
                'flat_id' => $flat->id,
                'bill_month' => '2026-07',
                'reading_date' => '2026-07-31',
                'previous_m3' => 46.28,
                'current_m3' => 50.00,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reading.flat_id', $flat->id)
            ->assertJsonPath('reading.current_m3', 50);

        $this->assertTrue(
            GasMeterReading::query()
                ->where('flat_id', $flat->id)
                ->whereDate('bill_month', '2026-07-01')
                ->where('current_m3', 50)
                ->exists()
        );
    }

    #[Test]
    public function update_via_json_saves_only_that_reading(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $flatA = Flat::query()->where('name', '2A')->firstOrFail();
        $flatB = Flat::query()->where('name', '2B')->firstOrFail();

        $readingA = GasMeterReading::query()->updateOrCreate(
            ['flat_id' => $flatA->id, 'bill_month' => '2026-07-01'],
            [
                'reading_date' => '2026-07-31',
                'previous_m3' => 10,
                'current_m3' => 11,
                'confirmed_m3' => 11,
            ]
        );
        $readingB = GasMeterReading::query()->updateOrCreate(
            ['flat_id' => $flatB->id, 'bill_month' => '2026-07-01'],
            [
                'reading_date' => '2026-07-31',
                'previous_m3' => 20,
                'current_m3' => 21,
                'confirmed_m3' => 21,
            ]
        );

        $this->actingAs($user)
            ->putJson(route('admin.gas-readings.update', $readingA), [
                'reading_date' => '2026-07-31',
                'previous_m3' => 10,
                'current_m3' => 15.5,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reading.id', $readingA->id)
            ->assertJsonPath('reading.current_m3', 15.5);

        $this->assertEquals(15.5, (float) $readingA->fresh()->current_m3);
        $this->assertEquals(21.0, (float) $readingB->fresh()->current_m3);
    }
}
