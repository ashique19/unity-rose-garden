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

class OfflineGasPhotoFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function photo_upload_stores_image_without_ocr(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $flat = Flat::query()->where('name', '2A')->firstOrFail();

        GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->delete();

        $response = $this->actingAs($user)
            ->postJson(route('admin.gas-readings.photo', $flat), [
                'bill_month' => '2026-07',
                'photo' => UploadedFile::fake()->image('meter.jpg', 800, 600),
            ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'Photo saved for later use. Enter the reading manually when ready.');

        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $this->assertNotNull($reading->photo_path);
        $this->assertNull($reading->gemini_suggestion);

        $this->actingAs($user)
            ->get(route('admin.gas-readings.index', ['month' => '2026-07']))
            ->assertOk()
            ->assertSee('Saved for later')
            ->assertDontSee('>OCR</button>', false)
            ->assertDontSee('Set <code>GEMINI_API_KEY</code>', false);
    }

    #[Test]
    public function ocr_requires_server_photo_and_does_not_overwrite_confirmed(): void
    {
        $this->seed(DatabaseSeeder::class);
        config(['services.gemini.api_key' => 'test-key']);
        Storage::fake('public');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '49.50']]],
                ]],
            ], 200),
        ]);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $flat = Flat::query()->where('name', '2A')->firstOrFail();
        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-06-01')
            ->firstOrFail();

        $path = UploadedFile::fake()->image('meter.jpg')->store('meter-readings/'.$flat->id, 'public');
        $original = (float) $reading->confirmed_m3;
        $reading->update(['photo_path' => $path, 'gemini_suggestion' => null]);

        $this->actingAs($user)
            ->postJson(route('admin.gas-readings.ocr', $flat), ['bill_month' => '2026-06'])
            ->assertOk()
            ->assertJsonPath('gemini_suggestion', 49.5);

        $reading->refresh();
        $this->assertEquals(49.5, (float) $reading->gemini_suggestion);
        $this->assertEquals($original, (float) $reading->confirmed_m3);
        $this->assertEquals($original, (float) $reading->current_m3);
    }

    #[Test]
    public function ocr_fails_when_no_photo_on_server(): void
    {
        $this->seed(DatabaseSeeder::class);
        config(['services.gemini.api_key' => 'test-key']);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $flat = Flat::query()->where('name', '2A')->firstOrFail();

        GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->delete();

        $this->actingAs($user)
            ->postJson(route('admin.gas-readings.ocr', $flat), ['bill_month' => '2026-07'])
            ->assertStatus(422);
    }

    #[Test]
    public function after_photo_upload_reading_value_can_be_saved_without_reload(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $flat = Flat::query()->where('name', '2A')->firstOrFail();

        GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->delete();

        $photo = $this->actingAs($user)
            ->postJson(route('admin.gas-readings.photo', $flat), [
                'bill_month' => '2026-07',
                'photo' => UploadedFile::fake()->image('meter.jpg', 800, 600),
            ]);

        $photo->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reading.confirmed', false)
            ->assertJsonStructure([
                'reading' => ['id', 'update_url', 'destroy_url', 'flat_id'],
            ]);

        $draft = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $this->assertNull($draft->confirmed_m3);
        $this->assertNotNull($draft->photo_path);

        // Garage UI still posts to store after a photo if the row was not converted —
        // store must upsert the photo draft instead of failing unique.
        $this->actingAs($user)
            ->postJson(route('admin.gas-readings.store'), [
                'flat_id' => $flat->id,
                'bill_month' => '2026-07',
                'reading_date' => '2026-07-31',
                'previous_m3' => 46.28,
                'current_m3' => 51.10,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reading.id', $draft->id)
            ->assertJsonPath('reading.current_m3', 51.1)
            ->assertJsonPath('reading.confirmed', true);

        $draft->refresh();
        $this->assertEquals(51.10, (float) $draft->confirmed_m3);
        $this->assertEquals(51.10, (float) $draft->current_m3);
        $this->assertNotNull($draft->photo_path);

        $this->actingAs($user)
            ->get(route('admin.gas-readings.index', ['month' => '2026-07']))
            ->assertOk()
            ->assertSee('Browse months')
            ->assertSee('Entered')
            ->assertDontSee('Photo only — enter reading');
    }

    #[Test]
    public function photo_only_draft_is_editable_on_month_page(): void
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
            ->postJson(route('admin.gas-readings.photo', $flat), [
                'bill_month' => '2026-07',
                'photo' => UploadedFile::fake()->image('meter.jpg', 800, 600),
            ])
            ->assertOk();

        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.gas-readings.index', ['month' => '2026-07']))
            ->assertOk()
            ->assertSee('Photo only — enter reading')
            ->assertSee('gas-update-'.$reading->id, false)
            ->assertSee('Browse months');

        $this->actingAs($user)
            ->putJson(route('admin.gas-readings.update', $reading), [
                'reading_date' => '2026-07-31',
                'previous_m3' => (float) $reading->previous_m3,
                'current_m3' => ((float) $reading->previous_m3) + 2.5,
            ])
            ->assertOk()
            ->assertJsonPath('reading.confirmed', true);
    }

    #[Test]
    public function month_page_shows_total_used_at_bottom(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('phone', '01785636359')->firstOrFail();
        $flatA = Flat::query()->where('name', '2A')->firstOrFail();
        $flatB = Flat::query()->where('name', '2B')->firstOrFail();

        GasMeterReading::query()->whereDate('bill_month', '2026-08-01')->delete();

        GasMeterReading::query()->create([
            'flat_id' => $flatA->id,
            'bill_month' => '2026-08-01',
            'reading_date' => '2026-08-31',
            'previous_m3' => 10,
            'current_m3' => 12.5,
            'confirmed_m3' => 12.5,
        ]);
        GasMeterReading::query()->create([
            'flat_id' => $flatB->id,
            'bill_month' => '2026-08-01',
            'reading_date' => '2026-08-31',
            'previous_m3' => 20,
            'current_m3' => 23.25,
            'confirmed_m3' => 23.25,
        ]);
        // Photo-only draft must not count toward total used.
        GasMeterReading::query()->create([
            'flat_id' => Flat::query()->where('name', '3B')->value('id'),
            'bill_month' => '2026-08-01',
            'reading_date' => '2026-08-31',
            'previous_m3' => 5,
            'current_m3' => 5,
            'confirmed_m3' => null,
            'photo_path' => 'meter-readings/draft.jpg',
        ]);

        $this->actingAs($user)
            ->get(route('admin.gas-readings.index', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('Total used')
            ->assertSee('id="gas-total-used"', false)
            ->assertSee('>5.75<', false);
    }
}
