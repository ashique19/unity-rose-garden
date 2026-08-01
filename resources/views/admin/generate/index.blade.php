@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container" style="max-width: 720px;">
        <h1 class="fw-bold text-dark mb-1">Generate monthly statements</h1>
        <p class="text-muted mb-4">
            Upserts statements and lines from gas readings and other charges.
            Existing collections are preserved.
        </p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
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

        <div class="bg-white border rounded-3 shadow-sm p-4 mb-4">
            <form method="get" class="mb-4">
                <label for="preview_month" class="form-label">Check readiness for month</label>
                <input type="month" name="month" id="preview_month" class="form-control"
                       value="{{ $selectedMonth->format('Y-m') }}" onchange="this.form.submit()">
            </form>

            @php
                $gas = $readiness['gas'];
                $commonBills = $readiness['common'] ?? [];
                $commonMissing = collect($commonBills)->where('entered', false)->pluck('label');
            @endphp

            @if($readiness['ready'])
                <div class="alert alert-success mb-3">
                    All required enabled charges are entered for {{ $selectedMonth->format('F Y') }}.
                    @if($commonMissing->isNotEmpty())
                        Water bills still optional and not entered yet: {{ $commonMissing->implode(', ') }}.
                    @endif
                </div>
            @else
                <div class="alert alert-warning mb-3">
                    {{ $readiness['pending_count'] }} required item(s) still pending for {{ $selectedMonth->format('F Y') }}.
                </div>
            @endif

            <h2 class="h6 fw-bold mb-2">Charge readiness</h2>
            <ul class="list-unstyled mb-0 small">
                <li class="mb-3 pb-3 border-bottom">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="fw-semibold">
                            <a href="{{ route('admin.gas-readings.index', ['month' => $selectedMonth->format('Y-m')]) }}">Gas readings</a>
                        </span>
                        <span class="{{ $gas['pending_flats'] ? 'text-danger' : 'text-success' }}">
                            {{ $gas['entered'] }} / {{ $gas['required'] }} flats
                        </span>
                    </div>
                    @if($gas['pending_flats'])
                        <div class="text-danger mt-1">Pending: {{ implode(', ', $gas['pending_flats']) }}</div>
                    @else
                        <div class="text-muted mt-1">All gas-enabled flats have readings.</div>
                    @endif
                </li>

                @foreach($commonBills as $common)
                    <li class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="fw-semibold">
                                <a href="{{ route('admin.water.index', ['month' => $selectedMonth->format('Y-m')]) }}">{{ $common['label'] }}</a>
                                <span class="text-muted fw-normal">(optional · equal split)</span>
                            </span>
                            <span class="{{ $common['entered'] ? 'text-success' : 'text-muted' }}">
                                {{ $common['entered'] ? 'Entered' : 'Not entered' }}
                            </span>
                        </div>
                        <div class="text-muted mt-1">
                            Would apply to {{ $common['enabled_flats'] }} enabled flat(s) when entered.
                        </div>
                    </li>
                @endforeach

                @foreach($readiness['other'] as $item)
                    <li class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="fw-semibold">
                                @if($item['covered_by_template'])
                                    {{ $item['label'] }}
                                    <span class="text-muted fw-normal">(building template)</span>
                                @else
                                    <a href="{{ route('admin.other-charges.index', ['month' => $selectedMonth->format('Y-m')]) }}">{{ $item['label'] }}</a>
                                @endif
                            </span>
                            <span class="{{ $item['pending_flats'] ? 'text-danger' : 'text-success' }}">
                                @if($item['covered_by_template'])
                                    Ready for {{ $item['required'] }} flats
                                @else
                                    {{ $item['entered'] }} / {{ $item['required'] }} flats
                                @endif
                            </span>
                        </div>
                        @if($item['covered_by_template'])
                            <div class="text-muted mt-1">Covered by charge template for all enabled flats.</div>
                        @elseif($item['pending_flats'])
                            <div class="text-danger mt-1">Pending: {{ implode(', ', $item['pending_flats']) }}</div>
                        @else
                            <div class="text-muted mt-1">Custom charges entered for all enabled flats.</div>
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="small text-muted">
                <a href="{{ route('admin.flat-bill-type-settings.index') }}">Check flat × bill type toggles</a>
            </div>
        </div>

        <div class="bg-white border rounded-3 shadow-sm p-4">
            <form method="post" action="{{ route('admin.generate.store') }}">
                @csrf
                <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                <div class="mb-3">
                    <label class="form-label">Bill month</label>
                    <input type="text" class="form-control" value="{{ $selectedMonth->format('F Y') }}" disabled>
                </div>
                <div class="mb-3">
                    <label for="price_per_kg" class="form-label">Gas price per kg (৳)</label>
                    <input type="number" step="0.01" min="0" name="price_per_kg" id="price_per_kg"
                           class="form-control" value="{{ old('price_per_kg', $defaultPricePerKg) }}" required>
                </div>

                @if($existingCount > 0)
                    <div class="alert alert-warning small">
                        {{ $existingCount }} statement(s) already exist for this month.
                        Generating again will refresh lines and keep collections.
                    </div>
                @endif

                @if(! $readiness['ready'])
                    <div class="alert alert-secondary small">
                        You can still generate now; missing gas readings will be skipped for those flats.
                        Pending custom charges will not create lines until entered.
                    </div>
                @endif

                <button type="submit" class="btn btn-primary"
                        onclick="return confirm('Generate / refresh statements for {{ $selectedMonth->format('F Y') }}?')">
                    Generate / refresh
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
