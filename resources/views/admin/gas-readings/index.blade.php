@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20" id="gas-readings-offline"
     data-month="{{ $selectedMonth->format('Y-m') }}"
     data-gemini-ready="{{ $geminiReady ? '1' : '0' }}">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-3">
            <div>
                <h1 class="fw-bold text-dark mb-1">Gas meter readings</h1>
                <p class="text-muted mb-0">
                    Garage workflow: <strong>Photo</strong> stores a small image in this browser (works offline),
                    then <strong>Sync</strong> uploads when you have signal. <strong>OCR</strong> runs only after the photo is on the server.
                </p>
            </div>
            <form method="get" class="d-flex align-items-center gap-2">
                <label for="month" class="form-label mb-0">Month</label>
                <input type="month" name="month" id="month" class="form-control"
                       value="{{ $selectedMonth->format('Y-m') }}" onchange="this.form.submit()">
            </form>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
            <span id="network-status" class="badge text-bg-secondary">…</span>
            <button type="button" id="sync-photos-btn" class="btn btn-sm btn-outline-primary">
                Sync queued photos
                <span id="offline-queue-count" class="badge text-bg-warning ms-1 d-none">0</span>
            </button>
            <span id="sync-status" class="small text-muted"></span>
            @unless($geminiReady)
                <span class="small text-warning">Set <code>GEMINI_API_KEY</code> to enable OCR.</span>
            @endunless
        </div>

        <input type="file" id="offline-camera-input" accept="image/*" capture="environment" class="d-none">

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

        <div class="table-responsive bg-white border rounded-3 shadow-sm">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Flat</th>
                        <th>Reading date</th>
                        <th>Previous m³</th>
                        <th>Current m³</th>
                        <th>Used</th>
                        <th>Photo / OCR</th>
                        <th style="min-width: 220px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $flat = $row['flat'];
                            $reading = $row['reading'];
                            $hasPhoto = filled($reading?->photo_path);
                            $readingDateDefault = $reading?->reading_date?->format('Y-m-d')
                                ?? $selectedMonth->copy()->endOfMonth()->format('Y-m-d');
                        @endphp
                        @if($reading)
                            <tr>
                                <form method="post" action="{{ route('admin.gas-readings.update', $reading) }}" id="gas-update-{{ $reading->id }}">
                                    @csrf
                                    @method('PUT')
                                </form>
                                <td class="fw-semibold">
                                    {{ $flat->name }}
                                    @if($hasPhoto)
                                        <div class="mt-1">
                                            <img data-photo-thumb="{{ $flat->id }}"
                                                 src="{{ asset('storage/'.$reading->photo_path) }}"
                                                 alt="" class="rounded border" style="max-height: 40px;">
                                        </div>
                                    @else
                                        <img data-photo-thumb="{{ $flat->id }}" alt="" class="rounded border d-none" style="max-height: 40px;">
                                    @endif
                                </td>
                                <td>
                                    <input form="gas-update-{{ $reading->id }}" type="date" name="reading_date" class="form-control form-control-sm"
                                           value="{{ $readingDateDefault }}" required>
                                </td>
                                <td>
                                    <input form="gas-update-{{ $reading->id }}" type="number" step="0.01" min="0" name="previous_m3" class="form-control form-control-sm"
                                           value="{{ $reading->previous_m3 }}" required>
                                </td>
                                <td>
                                    <input form="gas-update-{{ $reading->id }}" type="number" step="0.01" min="0" name="current_m3"
                                           data-current-input="{{ $flat->id }}"
                                           class="form-control form-control-sm"
                                           value="{{ $reading->current_m3 }}" required>
                                </td>
                                <td>{{ number_format($reading->consumedM3(), 2) }}</td>
                                <td>
                                    <div data-ocr-value="{{ $flat->id }}" class="small">
                                        @if($reading->gemini_suggestion !== null)
                                            {{ number_format((float) $reading->gemini_suggestion, 2) }}
                                        @else
                                            —
                                        @endif
                                    </div>
                                    <div data-photo-status="{{ $flat->id }}" class="small photo-status text-muted">
                                        {{ $hasPhoto ? 'Photo on server' : 'No photo' }}
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-photo-btn="{{ $flat->id }}"
                                            data-upload-url="{{ route('admin.gas-readings.photo', $flat) }}"
                                            data-reading-date="{{ $readingDateDefault }}">Photo</button>
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                            data-ocr-btn="{{ $flat->id }}"
                                            data-ocr-url="{{ route('admin.gas-readings.ocr', $flat) }}"
                                            data-has-photo="{{ $hasPhoto ? '1' : '0' }}"
                                            {{ $hasPhoto && $geminiReady ? '' : 'disabled' }}>OCR</button>
                                    <button form="gas-update-{{ $reading->id }}" class="btn btn-sm btn-outline-primary">Save</button>
                                    <form method="post" action="{{ route('admin.gas-readings.destroy', $reading) }}" class="d-inline"
                                          onsubmit="return confirm('Delete reading for {{ $flat->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Del</button>
                                    </form>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <form method="post" action="{{ route('admin.gas-readings.store') }}" id="gas-store-{{ $flat->id }}">
                                    @csrf
                                    <input type="hidden" name="flat_id" value="{{ $flat->id }}">
                                    <input type="hidden" name="bill_month" value="{{ $selectedMonth->format('Y-m') }}">
                                </form>
                                <td class="fw-semibold">
                                    {{ $flat->name }}
                                    <img data-photo-thumb="{{ $flat->id }}" alt="" class="rounded border d-none mt-1" style="max-height: 40px;">
                                </td>
                                <td>
                                    <input form="gas-store-{{ $flat->id }}" type="date" name="reading_date" class="form-control form-control-sm"
                                           value="{{ $readingDateDefault }}" required>
                                </td>
                                <td>
                                    <input form="gas-store-{{ $flat->id }}" type="number" step="0.01" min="0" name="previous_m3" class="form-control form-control-sm"
                                           value="{{ $row['suggested_previous_m3'] }}" required>
                                </td>
                                <td>
                                    <input form="gas-store-{{ $flat->id }}" type="number" step="0.01" min="0" name="current_m3"
                                           data-current-input="{{ $flat->id }}"
                                           class="form-control form-control-sm" required>
                                </td>
                                <td class="text-muted">—</td>
                                <td>
                                    <div data-ocr-value="{{ $flat->id }}" class="small">—</div>
                                    <div data-photo-status="{{ $flat->id }}" class="small photo-status text-muted">No photo</div>
                                </td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-photo-btn="{{ $flat->id }}"
                                            data-upload-url="{{ route('admin.gas-readings.photo', $flat) }}"
                                            data-reading-date="{{ $readingDateDefault }}">Photo</button>
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                            data-ocr-btn="{{ $flat->id }}"
                                            data-ocr-url="{{ route('admin.gas-readings.ocr', $flat) }}"
                                            data-has-photo="0"
                                            disabled>OCR</button>
                                    <button form="gas-store-{{ $flat->id }}" class="btn btn-sm btn-primary">Add</button>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="/js/gas-meter-offline.js"></script>
@endsection
