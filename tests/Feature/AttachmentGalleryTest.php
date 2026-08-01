<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\User;
use App\Support\ImageResizer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachmentGalleryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function attachments_nav_requires_auth(): void
    {
        $this->get(route('admin.attachments.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_gallery(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.attachments.index'))
            ->assertOk()
            ->assertSee('Attachments')
            ->assertSee('Media gallery')
            ->assertSee('Upload bill photo');
    }

    #[Test]
    public function upload_resizes_to_max_1200_and_stores_jpeg(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $file = UploadedFile::fake()->image('wasa-bill.png', 2400, 1800);

        $this->actingAs($user)
            ->postJson(route('admin.attachments.store'), [
                'photo' => $file,
                'title' => 'WASA July',
                'bill_month' => '2026-07',
                'note' => 'Building copy',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('attachment.title', 'WASA July');

        $attachment = Attachment::query()->firstOrFail();
        $this->assertSame('WASA July', $attachment->title);
        $this->assertSame('image/jpeg', $attachment->mime);
        $this->assertLessThanOrEqual(ImageResizer::MAX_EDGE, $attachment->width);
        $this->assertLessThanOrEqual(ImageResizer::MAX_EDGE, $attachment->height);
        $this->assertSame(1200, $attachment->width);
        $this->assertSame(900, $attachment->height);
        $this->assertTrue(Storage::disk('public')->exists($attachment->path));
        $this->assertStringEndsWith('.jpg', $attachment->path);
        $this->assertNotEmpty($attachment->public_token);
        $this->assertStringContainsString('/media/'.$attachment->public_token, $attachment->url());
    }

    #[Test]
    public function media_url_is_public_and_serves_file(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('admin.attachments.store'), [
                'photo' => UploadedFile::fake()->image('bill.jpg', 800, 600),
                'title' => 'Share me',
            ])
            ->assertOk();

        $attachment = Attachment::query()->firstOrFail();

        // Guest (no auth) can open share link — avoids /storage symlink 403.
        auth()->logout();

        $this->get(route('attachments.media', $attachment->public_token))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    #[Test]
    public function can_delete_attachment(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($user)
            ->postJson(route('admin.attachments.store'), [
                'photo' => UploadedFile::fake()->image('bill.jpg', 800, 600),
                'title' => 'Temp',
            ])
            ->assertOk();

        $attachment = Attachment::query()->firstOrFail();
        $path = $attachment->path;

        $this->actingAs($user)
            ->delete(route('admin.attachments.destroy', $attachment))
            ->assertRedirect();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        $this->assertFalse(Storage::disk('public')->exists($path));
    }
}
