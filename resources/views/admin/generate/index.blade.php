@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container" style="max-width: 640px;">
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

        <div class="bg-white border rounded-3 shadow-sm p-4">
            <form method="post" action="{{ route('admin.generate.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="month" class="form-label">Bill month</label>
                    <input type="month" name="month" id="month" class="form-control"
                           value="{{ old('month', $selectedMonth->format('Y-m')) }}" required>
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

                <button type="submit" class="btn btn-primary"
                        onclick="return confirm('Generate / refresh statements for this month?')">
                    Generate / refresh
                </button>
            </form>
        </div>

        <div class="mt-4 small text-muted">
            <p class="mb-1">Before generating:</p>
            <ul class="mb-0">
                <li><a href="{{ route('admin.gas-readings.index', ['month' => $selectedMonth->format('Y-m')]) }}">Enter gas readings</a></li>
                <li><a href="{{ route('admin.water.index', ['month' => $selectedMonth->format('Y-m')]) }}">Enter common water</a> (optional)</li>
                <li><a href="{{ route('admin.other-charges.index', ['month' => $selectedMonth->format('Y-m')]) }}">Add other charges</a> (optional)</li>
                <li><a href="{{ route('admin.flat-bill-type-settings.index') }}">Check flat × bill type toggles</a></li>
            </ul>
        </div>
    </div>
</div>
@endsection
