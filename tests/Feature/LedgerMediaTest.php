<?php

namespace Tests\Feature;

use App\Models\AccountLedgerEntry;
use App\Models\Attachment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LedgerMediaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ledger_form_shows_gallery_media_picker(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('admin.attachments.store'), [
                'photo' => UploadedFile::fake()->image('bill.jpg', 800, 600),
                'title' => 'WASA receipt',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.ledger.index'))
            ->assertOk()
            ->assertSee('Media (optional)')
            ->assertSee('From gallery')
            ->assertSee('WASA receipt')
            ->assertSee('Media URLs');
    }

    #[Test]
    public function ledger_entry_can_link_gallery_attachment_and_urls(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('admin.attachments.store'), [
                'photo' => UploadedFile::fake()->image('bill.jpg', 800, 600),
                'title' => 'Deep tube-well bill',
            ])
            ->assertOk();

        $attachment = Attachment::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.ledger.store'), [
                'type' => 'cash_in',
                'amount' => 500,
                'entry_date' => '2026-08-01',
                'note' => 'Donation with proof',
                'attachment_ids' => [$attachment->id],
                'media_urls' => "https://example.com/scan.jpg\nhttps://cdn.example.org/x.png",
            ])
            ->assertRedirect(route('admin.ledger.index'));

        $entry = AccountLedgerEntry::query()->latest('id')->firstOrFail();
        $this->assertCount(3, $entry->media);
        $this->assertSame($attachment->id, $entry->media[0]['attachment_id']);
        $this->assertSame('https://example.com/scan.jpg', $entry->media[1]['url']);
        $this->assertSame('https://cdn.example.org/x.png', $entry->media[2]['url']);

        $this->actingAs($user)
            ->get(route('admin.ledger.index'))
            ->assertOk()
            ->assertSee('Photo')
            ->assertSee('URL 2')
            ->assertSee(route('attachments.media', $attachment->public_token), false);
    }

    #[Test]
    public function invalid_media_url_is_rejected(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->from(route('admin.ledger.index'))
            ->post(route('admin.ledger.store'), [
                'type' => 'cash_in',
                'amount' => 100,
                'entry_date' => '2026-08-01',
                'media_urls' => 'not-a-url',
            ])
            ->assertRedirect(route('admin.ledger.index'))
            ->assertSessionHasErrors('media_urls');
    }
}
