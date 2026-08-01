@extends('layouts.layout')

@section('content')
<style>
    .generated-bills-tree {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .generated-bills-tree > li + li {
        margin-top: 0.75rem;
    }
    .generated-bills-tree details {
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        background: #fff;
        overflow: hidden;
    }
    .generated-bills-tree summary {
        list-style: none;
        cursor: pointer;
        padding: 0.85rem 1rem;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 0.5rem 1rem;
        align-items: center;
    }
    .generated-bills-tree summary::-webkit-details-marker {
        display: none;
    }
    .generated-bills-tree summary::before {
        content: '▸';
        display: inline-block;
        width: 1rem;
        color: #6b7280;
        transition: transform 0.15s ease;
    }
    .generated-bills-tree details[open] > summary::before {
        transform: rotate(90deg);
    }
    .generated-bills-tree .tree-children {
        list-style: none;
        margin: 0;
        padding: 0 0 0.75rem 0;
        border-top: 1px solid #f1f5f9;
    }
    .generated-bills-tree .tree-children > li {
        position: relative;
        padding: 0.35rem 1rem 0.35rem 2.25rem;
    }
    .generated-bills-tree .tree-children > li::before {
        content: '';
        position: absolute;
        left: 1.35rem;
        top: 0;
        bottom: 0;
        border-left: 1px solid #d1d5db;
    }
    .generated-bills-tree .tree-children > li::after {
        content: '';
        position: absolute;
        left: 1.35rem;
        top: 1rem;
        width: 0.65rem;
        border-top: 1px solid #d1d5db;
    }
    .generated-bills-tree .tree-children > li:last-child::before {
        bottom: auto;
        height: 1rem;
    }
    .generated-bills-tree .head-row,
    .generated-bills-tree .flat-row {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 0.35rem 1rem;
        align-items: baseline;
    }
    .generated-bills-tree .head-details {
        border: none;
        border-radius: 0;
        background: transparent;
    }
    .generated-bills-tree .head-details > summary {
        padding: 0.35rem 0;
    }
    .generated-bills-tree .head-details > summary::before {
        content: '▹';
    }
    .generated-bills-tree .flat-children {
        list-style: none;
        margin: 0.25rem 0 0;
        padding: 0 0 0 1rem;
    }
    .generated-bills-tree .flat-children li {
        padding: 0.2rem 0;
        color: #4b5563;
        font-size: 0.9rem;
    }
</style>
<div class="features-section pt-20 pb-20">
    <div class="container" style="max-width: 860px;">
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

        <div class="bg-white border rounded-3 shadow-sm p-4 mt-4">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Generated Bills</h2>
                    <p class="text-muted small mb-0">Previous months with a tree breakdown by bill head.</p>
                </div>
            </div>

            @if($generatedMonths->isEmpty())
                <p class="text-muted mb-0">No statements generated yet.</p>
            @else
                <ul class="generated-bills-tree">
                    @foreach($generatedMonths as $generated)
                        @php
                            $isSelected = $generated['month_key'] === $selectedMonth->format('Y-m');
                        @endphp
                        <li>
                            <details @if($isSelected || $loop->first) open @endif>
                                <summary>
                                    <span>
                                        <span class="fw-semibold">{{ $generated['month']->format('F Y') }}</span>
                                        <span class="text-muted small ms-1">
                                            {{ $generated['statement_count'] }} flat{{ $generated['statement_count'] === 1 ? '' : 's' }}
                                        </span>
                                    </span>
                                    <span class="fw-semibold">৳{{ number_format($generated['total'], 2) }}</span>
                                </summary>

                                <ul class="tree-children">
                                    @forelse($generated['heads'] as $head)
                                        <li>
                                            <details class="head-details">
                                                <summary class="head-row">
                                                    <span class="fw-semibold">{{ $head['label'] }}</span>
                                                    <span>
                                                        <span class="text-muted small me-2">{{ $head['line_count'] }} line{{ $head['line_count'] === 1 ? '' : 's' }}</span>
                                                        <span class="fw-semibold">৳{{ number_format($head['total'], 2) }}</span>
                                                    </span>
                                                </summary>
                                                <ul class="flat-children">
                                                    @foreach($head['flats'] as $flatLine)
                                                        <li class="flat-row">
                                                            <span>
                                                                {{ $flatLine['flat'] }}
                                                                @if($flatLine['note'])
                                                                    <span class="text-muted">· {{ $flatLine['note'] }}</span>
                                                                @endif
                                                            </span>
                                                            <span>৳{{ number_format($flatLine['amount'], 2) }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </details>
                                        </li>
                                    @empty
                                        <li class="text-muted">No enabled bill lines for this month.</li>
                                    @endforelse
                                    <li class="head-row fw-semibold">
                                        <span>Month total</span>
                                        <span>৳{{ number_format($generated['total'], 2) }}</span>
                                    </li>
                                </ul>

                                <div class="px-3 pb-3 d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="{{ route('admin.generate.index', ['month' => $generated['month_key']]) }}">
                                        Open in generator
                                    </a>
                                    <a class="btn btn-sm btn-outline-secondary"
                                       href="{{ route('public.statements.print-building', ['month' => $generated['month_key']]) }}"
                                       target="_blank" rel="noopener">
                                        Print building bills
                                    </a>
                                </div>
                            </details>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
