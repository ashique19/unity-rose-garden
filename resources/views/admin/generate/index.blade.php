@extends('layouts.layout')

@section('content')
<style>
    .generate-page {
        font-size: 1.05rem;
        line-height: 1.55;
    }
    .generate-page .page-lead {
        font-size: 1.05rem;
    }
    .generate-page .section-card {
        padding: 1.75rem 1.5rem;
        margin-bottom: 1.5rem;
    }
    @media (min-width: 768px) {
        .generate-page .section-card {
            padding: 2rem 2rem;
        }
    }
    .generate-page .section-title {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }
    .generate-page .form-label {
        font-size: 1rem;
        margin-bottom: 0.65rem;
    }
    .generate-page .form-control {
        font-size: 1.05rem;
        padding: 0.7rem 0.9rem;
        min-height: 3rem;
    }
    .generate-page .readiness-table th,
    .generate-page .readiness-table td {
        font-size: 1.05rem !important;
        padding: 0.95rem 1rem !important;
        vertical-align: middle;
    }
    .generate-page .readiness-table th {
        font-weight: 600;
    }
    .generate-page .status-note {
        font-size: 0.95rem;
        margin-top: 0.4rem;
    }
    .generate-page .meta-link {
        font-size: 0.98rem;
        margin-top: 1.25rem;
    }
    .generate-page .alert {
        font-size: 1rem;
        padding: 1rem 1.15rem;
    }
    .generate-page .btn-generate {
        font-size: 1.05rem;
        padding: 0.75rem 1.35rem;
    }
    .generate-page .badge {
        font-size: 0.9rem;
        padding: 0.45rem 0.75rem;
    }
</style>
<div class="features-section pt-20 pb-20">
    <div class="container generate-page" style="max-width: 760px;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4 pb-2">
            <div>
                <h1 class="fw-bold text-dark mb-2">Generate month</h1>
                <p class="text-muted page-lead mb-0">
                    Check charge readiness, then create or refresh statements for one month.
                </p>
            </div>
            <a href="{{ route('admin.generate.history') }}" class="btn btn-outline-secondary">
                Generated Bills
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
                <div class="mt-2">
                    <a href="{{ route('admin.generate.history', ['month' => $selectedMonth->format('Y-m')]) }}"
                       class="alert-link">View Generated Bills</a>
                </div>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $gas = $readiness['gas'];
            $commonBills = $readiness['common'] ?? [];
            $commonMissing = collect($commonBills)->where('entered', false)->pluck('label');
            $monthKey = $selectedMonth->format('Y-m');
        @endphp

        <div class="bg-white border rounded-3 shadow-sm section-card">
            <form method="get" class="mb-0">
                <label for="preview_month" class="form-label fw-semibold">Bill month</label>
                <input type="month" name="month" id="preview_month" class="form-control"
                       value="{{ $monthKey }}" onchange="this.form.submit()">
            </form>
        </div>

        <div class="bg-white border rounded-3 shadow-sm section-card">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="section-title mb-0">Charge readiness</h2>
                @if($readiness['ready'])
                    <span class="badge text-bg-success">Ready</span>
                @else
                    <span class="badge text-bg-warning text-dark">{{ $readiness['pending_count'] }} pending</span>
                @endif
            </div>

            @if($readiness['ready'])
                <p class="text-success mb-4">
                    All required charges are entered for {{ $selectedMonth->format('F Y') }}.
                    @if($commonMissing->isNotEmpty())
                        Optional water still missing: {{ $commonMissing->implode(', ') }}.
                    @endif
                </p>
            @else
                <p class="text-warning-emphasis mb-4">
                    Finish pending items below, or generate anyway (missing gas flats are skipped).
                </p>
            @endif

            <div class="table-responsive">
                <table class="table readiness-table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Charge</th>
                            <th class="text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-start">
                                <a href="{{ route('admin.gas-readings.index', ['month' => $monthKey]) }}">Gas readings</a>
                                @if($gas['pending_flats'])
                                    <div class="text-danger status-note">Pending: {{ implode(', ', $gas['pending_flats']) }}</div>
                                @endif
                            </td>
                            <td class="text-end {{ $gas['pending_flats'] ? 'text-danger' : 'text-success' }}">
                                {{ $gas['entered'] }} / {{ $gas['required'] }}
                            </td>
                        </tr>

                        @foreach($commonBills as $common)
                            <tr>
                                <td class="text-start">
                                    <a href="{{ route('admin.water.index', ['month' => $monthKey]) }}">{{ $common['label'] }}</a>
                                    <span class="text-muted"> · optional</span>
                                </td>
                                <td class="text-end {{ $common['entered'] ? 'text-success' : 'text-muted' }}">
                                    {{ $common['entered'] ? 'Entered' : 'Not entered' }}
                                </td>
                            </tr>
                        @endforeach

                        @foreach($readiness['other'] as $item)
                            <tr>
                                <td class="text-start">
                                    @if($item['covered_by_template'])
                                        {{ $item['label'] }}
                                        <span class="text-muted"> · template</span>
                                    @else
                                        <a href="{{ route('admin.other-charges.index', ['month' => $monthKey]) }}">{{ $item['label'] }}</a>
                                        @if($item['pending_flats'])
                                            <div class="text-danger status-note">Pending: {{ implode(', ', $item['pending_flats']) }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-end {{ $item['pending_flats'] ? 'text-danger' : 'text-success' }}">
                                    @if($item['covered_by_template'])
                                        Ready
                                    @else
                                        {{ $item['entered'] }} / {{ $item['required'] }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="text-muted meta-link mb-0">
                <a href="{{ route('admin.flat-bill-type-settings.index') }}">Flat × bill type toggles</a>
            </div>
        </div>

        <div class="bg-white border rounded-3 shadow-sm section-card mb-0">
            <h2 class="section-title">Generate / refresh</h2>
            <form method="post" action="{{ route('admin.generate.store') }}">
                @csrf
                <input type="hidden" name="month" value="{{ $monthKey }}">

                <div class="mb-4">
                    <label class="form-label">Month</label>
                    <input type="text" class="form-control" value="{{ $selectedMonth->format('F Y') }}" disabled>
                </div>
                <div class="mb-4">
                    <label for="price_per_kg" class="form-label">Gas price per kg (৳)</label>
                    <input type="number" step="0.01" min="0" name="price_per_kg" id="price_per_kg"
                           class="form-control" value="{{ old('price_per_kg', $defaultPricePerKg) }}" required>
                </div>

                @if($existingCount > 0)
                    <div class="alert alert-warning mb-4">
                        {{ $existingCount }} statement(s) already exist for this month.
                        Generating again refreshes lines and keeps collections.
                        <a href="{{ route('admin.generate.history', ['month' => $monthKey]) }}" class="alert-link">
                            Review existing bills
                        </a>
                    </div>
                @endif

                @if(! $readiness['ready'])
                    <div class="alert alert-secondary mb-4">
                        You can still generate now. Missing gas readings are skipped; pending custom charges won’t create lines until entered.
                    </div>
                @endif

                <button type="submit" class="btn btn-primary btn-generate"
                        onclick="return confirm('Generate / refresh statements for {{ $selectedMonth->format('F Y') }}?')">
                    Generate / refresh {{ $selectedMonth->format('F Y') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
