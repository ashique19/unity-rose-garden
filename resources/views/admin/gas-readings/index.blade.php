@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20" id="gas-readings-offline"
     data-month="{{ $selectedMonth->format('Y-m') }}">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-3">
            <div>
                <h1 class="fw-bold text-dark mb-1">Gas meter readings</h1>
                <p class="text-muted mb-0">
                    View and edit readings for <strong>{{ $selectedMonth->format('F Y') }}</strong>.
                    Take a photo in the garage (works offline), then enter the m³ value for each flat.
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('admin.gas-readings.index', ['month' => $previousMonth->format('Y-m')]) }}"
                   class="btn btn-sm btn-outline-secondary">← {{ $previousMonth->format('M Y') }}</a>
                <form method="get" class="d-flex align-items-center gap-2 mb-0">
                    <label for="month" class="form-label mb-0">Month</label>
                    <input type="month" name="month" id="month" class="form-control"
                           value="{{ $selectedMonth->format('Y-m') }}" onchange="this.form.submit()">
                </form>
                <a href="{{ route('admin.gas-readings.index', ['month' => $nextMonth->format('Y-m')]) }}"
                   class="btn btn-sm btn-outline-secondary">{{ $nextMonth->format('M Y') }} →</a>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="badge text-bg-success">Entered {{ $confirmedCount }}</span>
            @if($photoOnlyCount > 0)
                <span class="badge text-bg-warning">Photo only {{ $photoOnlyCount }}</span>
            @endif
            @if($missingCount > 0)
                <span class="badge text-bg-secondary">Missing {{ $missingCount }}</span>
            @endif
            <span class="text-muted small">of {{ $rows->count() }} gas flats</span>
        </div>

        @if($availableMonths->isNotEmpty())
            <div class="mb-4">
                <div class="text-muted small mb-2">Browse months</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($availableMonths as $monthRow)
                        @php
                            $ym = \Carbon\Carbon::parse($monthRow->bill_month)->format('Y-m');
                            $label = \Carbon\Carbon::parse($monthRow->bill_month)->format('M Y');
                            $active = $ym === $selectedMonth->format('Y-m');
                        @endphp
                        <a href="{{ route('admin.gas-readings.index', ['month' => $ym]) }}"
                           class="btn btn-sm {{ $active ? 'btn-dark' : 'btn-outline-secondary' }}">
                            {{ $label }}
                            <span class="opacity-75">({{ (int) $monthRow->confirmed_count }}/{{ (int) $monthRow->readings_count }})</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
            <span id="network-status" class="badge text-bg-secondary">…</span>
            <button type="button" id="sync-photos-btn" class="btn btn-sm btn-outline-primary">
                Sync queued photos
                <span id="offline-queue-count" class="badge text-bg-warning ms-1 d-none">0</span>
            </button>
            <span id="sync-status" class="small text-muted"></span>
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
                        <th>Photo</th>
                        <th style="min-width: 220px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $flat = $row['flat'];
                            $reading = $row['reading'];
                            $confirmed = $reading?->isConfirmed() ?? false;
                            $hasPhoto = filled($reading?->photo_path);
                            $readingDateDefault = $reading?->reading_date?->format('Y-m-d')
                                ?? $selectedMonth->copy()->endOfMonth()->format('Y-m-d');
                            $formId = $reading ? 'gas-update-'.$reading->id : 'gas-store-'.$flat->id;
                            $photoUrl = $hasPhoto
                                ? route('admin.gas-readings.photo-file', ['flat' => $flat, 'month' => $selectedMonth->format('Y-m')])
                                : null;
                            $currentValue = $confirmed ? $reading->current_m3 : '';
                        @endphp
                        <tr data-flat-row="{{ $flat->id }}"
                            data-flat-name="{{ $flat->name }}"
                            data-row-mode="{{ $reading ? 'update' : 'create' }}"
                            @if($reading) data-reading-id="{{ $reading->id }}" @endif>
                            <td class="fw-semibold">
                                {{ $flat->name }}
                                @if($hasPhoto && ! $confirmed)
                                    <div class="small text-warning">Photo only — enter reading</div>
                                @endif
                                @if($hasPhoto)
                                    <div class="mt-1">
                                        <a href="{{ $photoUrl }}" target="_blank" rel="noopener">
                                            <img data-photo-thumb="{{ $flat->id }}"
                                                 src="{{ $photoUrl }}"
                                                 alt="" class="rounded border" style="max-height: 40px;">
                                        </a>
                                    </div>
                                @else
                                    <img data-photo-thumb="{{ $flat->id }}" alt="" class="rounded border d-none" style="max-height: 40px;">
                                @endif
                            </td>
                            <td>
                                <input form="{{ $formId }}" type="date" name="reading_date" class="form-control form-control-sm"
                                       value="{{ $readingDateDefault }}" required>
                            </td>
                            <td>
                                <input form="{{ $formId }}" type="number" step="0.01" min="0" name="previous_m3" class="form-control form-control-sm"
                                       value="{{ $reading ? $reading->previous_m3 : $row['suggested_previous_m3'] }}" required>
                            </td>
                            <td>
                                <input form="{{ $formId }}" type="number" step="0.01" min="0" name="current_m3"
                                       data-current-input="{{ $flat->id }}"
                                       class="form-control form-control-sm"
                                       value="{{ $currentValue }}"
                                       placeholder="{{ $hasPhoto && ! $confirmed ? 'Enter from photo' : '' }}"
                                       required>
                            </td>
                            <td @if(! $confirmed) data-used-cell="{{ $flat->id }}" class="text-muted" @endif>
                                {{ $confirmed ? number_format($reading->consumedM3(), 2) : '—' }}
                            </td>
                            <td>
                                <div data-photo-status="{{ $flat->id }}" class="small photo-status text-muted">
                                    {{ $hasPhoto ? 'Saved for later' : 'No photo' }}
                                </div>
                            </td>
                            <td class="text-nowrap" data-actions-cell="{{ $flat->id }}">
                                @if($reading)
                                    <form method="post"
                                          action="{{ route('admin.gas-readings.update', $reading) }}"
                                          id="{{ $formId }}"
                                          class="d-none gas-reading-save-form"
                                          data-save-mode="update"
                                          data-flat-id="{{ $flat->id }}">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                @else
                                    <form method="post"
                                          action="{{ route('admin.gas-readings.store') }}"
                                          id="{{ $formId }}"
                                          class="d-none gas-reading-save-form"
                                          data-save-mode="create"
                                          data-flat-id="{{ $flat->id }}"
                                          data-flat-name="{{ $flat->name }}">
                                        @csrf
                                        <input type="hidden" name="flat_id" value="{{ $flat->id }}">
                                        <input type="hidden" name="bill_month" value="{{ $selectedMonth->format('Y-m') }}">
                                    </form>
                                @endif

                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-photo-btn="{{ $flat->id }}"
                                        data-upload-url="{{ route('admin.gas-readings.photo', $flat) }}"
                                        data-reading-date="{{ $readingDateDefault }}">Photo</button>
                                <button type="submit"
                                        form="{{ $formId }}"
                                        class="btn btn-sm {{ $reading ? 'btn-outline-primary' : 'btn-primary' }}"
                                        data-save-btn="{{ $flat->id }}">
                                    {{ $reading ? 'Save' : 'Add' }}
                                </button>
                                @if($reading)
                                    <form method="post" action="{{ route('admin.gas-readings.destroy', $reading) }}" class="d-inline gas-reading-delete-form"
                                          onsubmit="return confirm('Delete reading for {{ $flat->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Del</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="/js/gas-meter-offline.js?v=3"></script>
@endsection
