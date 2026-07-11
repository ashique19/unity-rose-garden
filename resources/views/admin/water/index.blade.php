@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container" style="max-width: 720px;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold text-dark mb-1">Common water</h1>
                <p class="text-muted mb-0">Enter the building water bill; it splits equally among water-enabled flats on generate.</p>
            </div>
            <form method="get" class="d-flex align-items-center gap-2">
                <label for="month" class="form-label mb-0">Month</label>
                <input type="month" name="month" id="month" class="form-control"
                       value="{{ $selectedMonth->format('Y-m') }}" onchange="this.form.submit()">
            </form>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="bg-white border rounded-3 shadow-sm p-4 mb-4">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="text-muted small">Water-enabled flats</div>
                    <div class="fw-bold fs-4">{{ $enabledCount }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Share per flat</div>
                    <div class="fw-bold fs-4">
                        @if($shareError)
                            <span class="text-danger small">{{ $shareError }}</span>
                        @elseif($share !== null)
                            ৳ {{ number_format((float) $share, 2) }}
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Saved total</div>
                    <div class="fw-bold fs-4">
                        {{ $reading ? '৳ '.number_format((float) $reading->total_amount, 2) : '—' }}
                    </div>
                </div>
            </div>

            <form method="post" action="{{ route('admin.water.store') }}">
                @csrf
                <input type="hidden" name="bill_month" value="{{ $selectedMonth->format('Y-m') }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Total water bill (৳)</label>
                        <input type="number" step="0.01" min="0.01" name="total_amount" class="form-control"
                               value="{{ old('total_amount', $reading?->total_amount) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reading date</label>
                        <input type="date" name="reading_date" class="form-control"
                               value="{{ old('reading_date', $reading?->reading_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Previous reading (optional)</label>
                        <input type="number" step="0.01" min="0" name="previous_reading" class="form-control"
                               value="{{ old('previous_reading', $reading?->previous_reading) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Current reading (optional)</label>
                        <input type="number" step="0.01" min="0" name="current_reading" class="form-control"
                               value="{{ old('current_reading', $reading?->current_reading) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Note</label>
                        <input type="text" name="note" class="form-control" value="{{ old('note', $reading?->note) }}">
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary">Save water bill</button>
                    @if($reading)
                        <button form="water-delete" class="btn btn-outline-danger"
                                onclick="return confirm('Remove water entry for this month?')">Remove</button>
                    @endif
                    <a href="{{ route('admin.generate.index', ['month' => $selectedMonth->format('Y-m')]) }}"
                       class="btn btn-outline-secondary">Go to generate</a>
                </div>
            </form>
            @if($reading)
                <form id="water-delete" method="post" action="{{ route('admin.water.destroy') }}" class="d-none">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="month" value="{{ $selectedMonth->format('Y-m') }}">
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
