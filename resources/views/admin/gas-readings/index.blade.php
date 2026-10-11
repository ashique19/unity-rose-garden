@extends('layouts.layout')

@section('content')
@php
    $groupedRows = $rows->groupBy(function (array $row) {
        preg_match('/([A-Za-z])$/', $row['flat']->name, $m);

        return strtoupper($m[1] ?? '');
    });
@endphp
<div class="features-section pt-3 pt-md-4 pb-5" id="gas-readings-offline"
     data-month="{{ $selectedMonth->format('Y-m') }}">
    <div class="container px-3 px-md-4">
        <div class="gas-readings-header mb-3">
            <h1 class="fw-bold text-dark mb-1 fs-3 fs-md-2">Gas meter readings</h1>
            <p class="text-muted mb-0 small">
                <strong>{{ $selectedMonth->format('F Y') }}</strong> —
                photo in the garage (works offline), then enter m³ per flat.
            </p>
        </div>

        <div class="gas-month-nav d-flex align-items-stretch gap-2 mb-3">
            <a href="{{ route('admin.gas-readings.index', ['month' => $previousMonth->format('Y-m')]) }}"
               class="btn btn-outline-secondary flex-shrink-0 px-3"
               aria-label="Previous month">←</a>
            <form method="get" class="flex-grow-1 mb-0">
                <label for="month" class="visually-hidden">Month</label>
                <input type="month" name="month" id="month" class="form-control"
                       value="{{ $selectedMonth->format('Y-m') }}" onchange="this.form.submit()">
            </form>
            <a href="{{ route('admin.gas-readings.index', ['month' => $nextMonth->format('Y-m')]) }}"
               class="btn btn-outline-secondary flex-shrink-0 px-3"
               aria-label="Next month">→</a>
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
            <details class="mb-3 gas-browse-months">
                <summary class="text-muted small mb-0">Browse months</summary>
                <div class="d-flex flex-wrap gap-2 mt-2">
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
            </details>
        @endif

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
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

        <div class="gas-flat-list">
            @foreach($groupedRows as $wing => $wingRows)
                @if($wing !== '')
                    <h2 class="gas-wing-heading">Side {{ $wing }}</h2>
                @endif

                @foreach($wingRows as $row)
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
                    <article class="gas-flat-card"
                             data-flat-row="{{ $flat->id }}"
                             data-flat-name="{{ $flat->name }}"
                             data-row-mode="{{ $reading ? 'update' : 'create' }}"
                             @if($reading) data-reading-id="{{ $reading->id }}" @endif>
                        <div class="gas-flat-card__top" data-flat-name-cell>
                            <div class="gas-flat-card__identity">
                                <div class="gas-flat-card__name">{{ $flat->name }}</div>
                                @if($hasPhoto && ! $confirmed)
                                    <div class="small text-warning" data-photo-only-hint>Photo only — enter reading</div>
                                @endif
                                <div data-photo-status="{{ $flat->id }}" class="small photo-status text-muted">
                                    {{ $hasPhoto ? 'Saved for later' : 'No photo' }}
                                </div>
                            </div>
                            <div class="gas-flat-card__thumb">
                                @if($hasPhoto)
                                    <a href="{{ $photoUrl }}" target="_blank" rel="noopener">
                                        <img data-photo-thumb="{{ $flat->id }}"
                                             src="{{ $photoUrl }}"
                                             alt="Meter photo {{ $flat->name }}"
                                             class="rounded border">
                                    </a>
                                @else
                                    <img data-photo-thumb="{{ $flat->id }}" alt="" class="rounded border d-none">
                                @endif
                            </div>
                        </div>

                        <div class="gas-flat-card__fields">
                            <div>
                                <label class="form-label small mb-1" for="{{ $formId }}-date">Reading date</label>
                                <input form="{{ $formId }}" id="{{ $formId }}-date" type="date" name="reading_date"
                                       class="form-control" value="{{ $readingDateDefault }}" required>
                            </div>
                            <div class="gas-flat-card__meters">
                                <div>
                                    <label class="form-label small mb-1" for="{{ $formId }}-prev">Previous m³</label>
                                    <input form="{{ $formId }}" id="{{ $formId }}-prev" type="number" step="0.01" min="0"
                                           name="previous_m3" class="form-control"
                                           value="{{ $reading ? $reading->previous_m3 : $row['suggested_previous_m3'] }}" required>
                                </div>
                                <div>
                                    <label class="form-label small mb-1" for="{{ $formId }}-curr">Current m³</label>
                                    <input form="{{ $formId }}" id="{{ $formId }}-curr" type="number" step="0.01" min="0"
                                           name="current_m3"
                                           data-current-input="{{ $flat->id }}"
                                           class="form-control"
                                           value="{{ $currentValue }}"
                                           placeholder="{{ $hasPhoto && ! $confirmed ? 'Enter from photo' : '' }}"
                                           required>
                                </div>
                                <div>
                                    <div class="form-label small mb-1">Used</div>
                                    <div class="gas-flat-card__used {{ $confirmed ? '' : 'text-muted' }}"
                                         data-used-cell="{{ $flat->id }}">
                                        {{ $confirmed ? number_format($reading->consumedM3(), 2) : '—' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="gas-flat-card__actions" data-actions-cell="{{ $flat->id }}">
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

                            <button type="button" class="btn btn-outline-secondary flex-fill"
                                    data-photo-btn="{{ $flat->id }}"
                                    data-upload-url="{{ route('admin.gas-readings.photo', $flat) }}"
                                    data-reading-date="{{ $readingDateDefault }}">Photo</button>
                            <button type="submit"
                                    form="{{ $formId }}"
                                    class="btn flex-fill {{ $reading ? 'btn-outline-primary' : 'btn-primary' }}"
                                    data-save-btn="{{ $flat->id }}">
                                {{ $reading ? 'Save' : 'Add' }}
                            </button>
                            @if($reading)
                                <form method="post" action="{{ route('admin.gas-readings.destroy', $reading) }}"
                                      class="gas-reading-delete-form flex-fill"
                                      onsubmit="return confirm('Delete reading for {{ $flat->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100">Del</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            @endforeach
        </div>

        <div class="gas-total-bar mt-3" id="gas-total-bar">
            <span class="fw-semibold">Total used</span>
            <span id="gas-total-used" data-total-used class="fs-5 fw-bold">{{ number_format($totalUsedM3, 2) }}</span>
        </div>
    </div>
</div>

<style>
    .gas-month-nav .form-control {
        min-height: 2.75rem;
    }
    .gas-browse-months summary {
        cursor: pointer;
        list-style: none;
    }
    .gas-browse-months summary::-webkit-details-marker { display: none; }
    .gas-browse-months summary::after {
        content: ' ▾';
        opacity: 0.6;
    }
    .gas-wing-heading {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #64748b;
        margin: 1.25rem 0 0.65rem;
        padding-bottom: 0.35rem;
        border-bottom: 1px solid rgba(15, 23, 42, 0.1);
    }
    .gas-wing-heading:first-child { margin-top: 0.25rem; }
    .gas-flat-card {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 0.85rem;
        padding: 0.9rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .gas-flat-card__top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .gas-flat-card__name {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.1;
        color: #0f172a;
    }
    .gas-flat-card__thumb img {
        width: 3.5rem;
        height: 3.5rem;
        object-fit: cover;
        display: block;
    }
    .gas-flat-card__fields {
        display: grid;
        gap: 0.65rem;
    }
    .gas-flat-card__meters {
        display: grid;
        grid-template-columns: 1fr 1fr minmax(4rem, 0.7fr);
        gap: 0.5rem;
        align-items: end;
    }
    .gas-flat-card__used {
        min-height: 2.5rem;
        display: flex;
        align-items: center;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        padding: 0.35rem 0;
    }
    .gas-flat-card__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.85rem;
    }
    .gas-flat-card__actions .btn {
        min-height: 2.6rem;
    }
    .gas-total-bar {
        position: sticky;
        bottom: 0.5rem;
        z-index: 5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.85rem 1rem;
        background: #0f172a;
        color: #fff;
        border-radius: 0.85rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.25);
    }
    @media (min-width: 768px) {
        .gas-flat-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem 1rem;
            align-items: start;
        }
        .gas-wing-heading {
            grid-column: 1 / -1;
        }
        .gas-flat-card {
            margin-bottom: 0;
            height: 100%;
        }
        .gas-total-bar {
            bottom: 1rem;
            max-width: 28rem;
            margin-left: auto;
        }
    }
</style>
@endsection

@section('js')
<script src="/js/gas-meter-offline.js?v=5"></script>
@endsection
