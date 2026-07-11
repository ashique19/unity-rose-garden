@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <h1 class="fw-bold text-dark mb-1">Audit log</h1>
        <p class="text-muted mb-4">Recent admin actions.</p>

        <div class="table-responsive bg-white border rounded-3 shadow-sm">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Subject</th>
                        <th>Meta</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at?->format('d M Y H:i') }}</td>
                            <td>{{ $log->user?->name ?? '—' }}</td>
                            <td><code>{{ $log->action }}</code></td>
                            <td class="small">
                                @if($log->subject_type)
                                    {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small text-muted">
                                @if($log->meta)
                                    {{ \Illuminate\Support\Str::limit(json_encode($log->meta), 80) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No audit entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
