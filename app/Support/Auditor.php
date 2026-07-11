<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class Auditor
{
    public static function log(string $action, ?Model $subject = null, array $meta = []): void
    {
        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
            'ip_address' => Request::ip(),
        ]);
    }
}
