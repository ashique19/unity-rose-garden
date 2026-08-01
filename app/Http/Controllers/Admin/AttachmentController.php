<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Support\Auditor;
use App\Support\BillMonth;
use App\Support\ImageResizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttachmentController extends Controller
{
    public function index(Request $request): View
    {
        $monthQuery = $request->query('month');
        $month = null;
        if (is_string($monthQuery) && $monthQuery !== '') {
            $month = BillMonth::parse($monthQuery);
        }

        $attachments = Attachment::query()
            ->with('uploader')
            ->when($month, fn ($q) => $q->whereDate('bill_month', $month->toDateString()))
            ->orderByDesc('created_at')
            ->paginate(24)
            ->withQueryString();

        return view('admin.attachments.index', [
            'attachments' => $attachments,
            'selectedMonth' => $month,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'title' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'bill_month' => ['nullable', 'date_format:Y-m'],
        ]);

        $file = $data['photo'];
        $dir = 'attachments/'.now()->format('Y/m');
        $storedPath = $file->store($dir, 'public');
        $absolute = Storage::disk('public')->path($storedPath);

        try {
            $resized = ImageResizer::constrain($absolute, ImageResizer::MAX_EDGE, 82);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($storedPath);

            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['photo' => $e->getMessage()])->withInput();
        }

        // Path may change to .jpg after resize (same directory).
        $relative = $dir.'/'.pathinfo($storedPath, PATHINFO_FILENAME).'.jpg';
        $finalAbsolute = Storage::disk('public')->path($relative);

        if ($relative !== $storedPath && Storage::disk('public')->exists($storedPath)) {
            Storage::disk('public')->delete($storedPath);
        }

        if (! is_file($finalAbsolute)) {
            Storage::disk('public')->delete([$storedPath, $relative]);

            $message = 'Failed to store resized image.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['photo' => $message])->withInput();
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Bill photo';
            $title = Str::limit($title, 80, '');
        }

        $attachment = Attachment::query()->create([
            'title' => $title,
            'original_name' => $file->getClientOriginalName(),
            'path' => $relative,
            'mime' => $resized['mime'],
            'size_bytes' => is_file($finalAbsolute) ? (int) filesize($finalAbsolute) : 0,
            'width' => $resized['width'],
            'height' => $resized['height'],
            'bill_month' => ! empty($data['bill_month'])
                ? BillMonth::parse($data['bill_month'])->toDateString()
                : null,
            'note' => $data['note'] ?? null,
            'uploaded_by' => $request->user()?->id,
        ]);

        Auditor::log('attachment.uploaded', $attachment, [
            'title' => $attachment->title,
            'path' => $attachment->path,
            'width' => $attachment->width,
            'height' => $attachment->height,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Photo uploaded.',
                'attachment' => [
                    'id' => $attachment->id,
                    'title' => $attachment->title,
                    'url' => $attachment->url(),
                    'share_url' => $attachment->absoluteUrl(),
                    'width' => $attachment->width,
                    'height' => $attachment->height,
                    'size_bytes' => $attachment->size_bytes,
                    'bill_month' => optional($attachment->bill_month)?->format('Y-m'),
                ],
            ]);
        }

        return redirect()
            ->route('admin.attachments.index', array_filter([
                'month' => $data['bill_month'] ?? null,
            ]))
            ->with('success', 'Photo uploaded. Use Copy link to share.');
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        if ($attachment->path) {
            Storage::disk('public')->delete($attachment->path);
        }

        Auditor::log('attachment.deleted', $attachment, [
            'title' => $attachment->title,
            'path' => $attachment->path,
        ]);

        $attachment->delete();

        return back()->with('success', 'Attachment deleted.');
    }
}
