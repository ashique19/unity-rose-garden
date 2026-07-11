<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $from = $this->validDate($request->query('from'));
        $to = $this->validDate($request->query('to'));

        $logs = AuditLog::query()
            ->with('user')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';

                $query->where(function ($builder) use ($like, $q) {
                    $builder->where('action', 'like', $like)
                        ->orWhere('subject_type', 'like', $like)
                        ->orWhere('ip_address', 'like', $like)
                        ->orWhere('meta', 'like', $like)
                        ->orWhereHas('user', function ($userQuery) use ($like) {
                            $userQuery->where('name', 'like', $like)
                                ->orWhere('phone', 'like', $like);
                        });

                    if (ctype_digit($q)) {
                        $builder->orWhere('subject_id', (int) $q)
                            ->orWhere('user_id', (int) $q);
                    }
                });
            })
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,
            'q' => $q,
            'from' => $from,
            'to' => $to,
        ]);
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }
}
