@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <div class="mb-4">
            <h1 class="fw-bold text-dark mb-1">Audit log</h1>
            <p class="text-muted mb-0">Recent admin actions.</p>
        </div>

        <x-mobile-panel-toggles :search-open="request()->hasAny(['q', 'from', 'to'])">
            <x-slot:search>
                <form method="get" class="row g-2 align-items-end bg-white border rounded-3 shadow-sm p-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label" for="audit-q">Search</label>
                        <input type="search" name="q" id="audit-q" class="form-control" value="{{ $q }}"
                               placeholder="Action, user, subject, meta, IP…">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="audit-from">From</label>
                        <input type="date" name="from" id="audit-from" class="form-control" value="{{ $from }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="audit-to">To</label>
                        <input type="date" name="to" id="audit-to" class="form-control" value="{{ $to }}">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn-outline-primary">Search</button>
                        @if($q !== '' || $from || $to)
                            <a href="{{ route('admin.audit.index') }}" class="btn btn-outline-secondary">Clear</a>
                        @endif
                    </div>
                </form>
            </x-slot:search>
        </x-mobile-panel-toggles>

        @if($q !== '' || $from || $to)
            <p class="text-muted small mb-3">
                {{ $logs->total() }} match{{ $logs->total() === 1 ? '' : 'es' }}
                @if($q !== '') for “{{ $q }}”@endif
                @if($from || $to)
                    ·
                    {{ $from ? \Carbon\Carbon::parse($from)->format('d M Y') : '…' }}
                    –
                    {{ $to ? \Carbon\Carbon::parse($to)->format('d M Y') : '…' }}
                @endif
            </p>
        @endif

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
                            <td colspan="6" class="text-center text-muted py-4">
                                {{ ($q !== '' || $from || $to) ? 'No audit entries match your search.' : 'No audit entries yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
