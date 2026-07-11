@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container" style="max-width: 720px;">
        <a href="{{ route('admin.gas-readings.index', ['month' => $selectedMonth->format('Y-m')]) }}"
           class="text-muted text-decoration-none small">&larr; Gas readings</a>

        <h1 class="fw-bold text-dark mt-2 mb-1">Meter photo assist — {{ $flat->name }}</h1>
        <p class="text-muted mb-4">
            {{ $selectedMonth->format('F Y') }}.
            Gemini may suggest a reading; you must confirm or edit before it is saved.
            OCR never writes the bill by itself.
        </p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @unless($geminiReady)
            <div class="alert alert-warning">
                Set <code>GEMINI_API_KEY</code> in <code>.env</code> to enable photo suggestions.
                You can still enter readings manually below.
            </div>
        @endunless

        @php
            $photoPath = $pendingPhotoPath ?? $reading?->photo_path;
            $suggestion = $pendingSuggestion ?? $reading?->gemini_suggestion;
            $defaultCurrent = old('current_m3', $pendingSuggestion ?? $reading?->current_m3 ?? $suggestion);
        @endphp

        <div class="bg-white border rounded-3 shadow-sm p-4 mb-4">
            <h2 class="h5 fw-bold mb-3">1. Upload meter photo</h2>
            <form method="post" action="{{ route('admin.gas-readings.suggest', $flat) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="bill_month" value="{{ $selectedMonth->format('Y-m') }}">
                <div class="mb-3">
                    <input type="file" name="photo" accept="image/*" capture="environment" class="form-control" required
                           {{ $geminiReady ? '' : 'disabled' }}>
                </div>
                <button class="btn btn-outline-primary" {{ $geminiReady ? '' : 'disabled' }}>
                    Get Gemini suggestion
                </button>
            </form>

            @if($photoPath)
                <div class="mt-3">
                    <div class="text-muted small mb-1">Stored photo</div>
                    <img src="{{ asset('storage/'.$photoPath) }}" alt="Meter photo"
                         class="img-fluid rounded border" style="max-height: 240px;">
                </div>
            @endif

            @if($suggestion !== null)
                <div class="alert alert-info mt-3 mb-0">
                    Gemini suggestion: <strong>{{ number_format((float) $suggestion, 2) }} m³</strong>
                    — review in Current below, then confirm.
                </div>
            @endif
        </div>

        <div class="bg-white border rounded-3 shadow-sm p-4">
            <h2 class="h5 fw-bold mb-3">2. Confirm reading</h2>

            @if($reading)
                <form method="post" action="{{ route('admin.gas-readings.update', $reading) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
            @else
                <form method="post" action="{{ route('admin.gas-readings.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="flat_id" value="{{ $flat->id }}">
                    <input type="hidden" name="bill_month" value="{{ $selectedMonth->format('Y-m') }}">
            @endif

                    @if($photoPath)
                        <input type="hidden" name="photo_path" value="{{ $photoPath }}">
                    @endif
                    @if($suggestion !== null)
                        <input type="hidden" name="gemini_suggestion" value="{{ $suggestion }}">
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Reading date</label>
                            <input type="date" name="reading_date" class="form-control" required
                                   value="{{ old('reading_date', $reading?->reading_date?->format('Y-m-d') ?? $selectedMonth->copy()->endOfMonth()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Previous m³</label>
                            <input type="number" step="0.01" min="0" name="previous_m3" class="form-control" required
                                   value="{{ old('previous_m3', $reading?->previous_m3 ?? $suggestedPrevious) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current m³ (confirm / edit)</label>
                            <input type="number" step="0.01" min="0" name="current_m3" class="form-control" required
                                   value="{{ $defaultCurrent }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Replace photo (optional)</label>
                            <input type="file" name="photo" accept="image/*" class="form-control">
                        </div>
                    </div>

                    <button class="btn btn-primary mt-4">Confirm &amp; save reading</button>
                </form>
        </div>
    </div>
</div>
@endsection
