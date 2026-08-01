<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AttachmentMediaController extends Controller
{
    /**
     * Public media endpoint — avoids /storage symlink 403s on some hosts.
     */
    public function show(string $token): BinaryFileResponse
    {
        $attachment = Attachment::query()
            ->where('public_token', $token)
            ->first();

        if (! $attachment || ! $attachment->existsOnDisk()) {
            throw new NotFoundHttpException('Attachment not found.');
        }

        $absolute = $attachment->diskPath();
        $mime = $attachment->mime ?: (Storage::disk('public')->mimeType($attachment->path) ?: 'image/jpeg');
        $filename = ($attachment->title ?: 'attachment').'.jpg';

        return response()->file($absolute, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($filename).'"',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
