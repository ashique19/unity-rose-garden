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
            ->assertJsonPath('ok', true);

        $reading = GasMeterReading::query()
            ->where('flat_id', $flat->id)
            ->whereDate('bill_month', '2026-07-01')
            ->firstOrFail();

        $this->assertNotNull($reading->photo_path);
        $this->assertNull($reading->gemini_suggestion);
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
}
