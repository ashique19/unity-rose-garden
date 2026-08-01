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
        justify-content: flex-start;
        gap: 0.5rem 0.75rem;
        align-items: baseline;
        text-align: left;
    }
    .generated-bills-tree summary::-webkit-details-marker {
        display: none;
    }
    .generated-bills-tree summary::before {
        content: '▸';
        flex: 0 0 auto;
        display: inline-block;
        width: 1rem;
        color: #6b7280;
        transition: transform 0.15s ease;
        text-align: left;
    }
    .generated-bills-tree details[open] > summary::before {
        transform: rotate(90deg);
    }
    .generated-bills-tree .tree-label {
        flex: 1 1 auto;
        min-width: 0;
        text-align: left;
    }
    .generated-bills-tree .tree-amount {
        flex: 0 0 auto;
        margin-left: auto;
        text-align: right;
        white-space: nowrap;
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
        justify-content: flex-start;
        gap: 0.35rem 0.75rem;
        align-items: baseline;
        text-align: left;
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
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold text-dark mb-1">Generated Bills</h1>
                <p class="text-muted mb-0">Previous months with a tree breakdown by bill head.</p>
            </div>
            <a href="{{ route('admin.generate.index') }}" class="btn btn-outline-primary btn-sm">Back to generate</a>
        </div>

        @if($generatedMonths->isEmpty())
            <div class="bg-white border rounded-3 shadow-sm p-4">
                <p class="text-muted mb-3">No statements generated yet.</p>
                <a href="{{ route('admin.generate.index') }}" class="btn btn-primary btn-sm">Generate a month</a>
            </div>
        @else
            <ul class="generated-bills-tree">
                @foreach($generatedMonths as $generated)
                    @php
                        $isFocused = $focusMonth && $generated['month_key'] === $focusMonth->format('Y-m');
                    @endphp
                    <li>
                        <details @if($isFocused || (! $focusMonth && $loop->first)) open @endif>
                            <summary>
                                <span class="tree-label">
                                    <span class="fw-semibold">{{ $generated['month']->format('F Y') }}</span>
                                    <span class="text-muted small ms-1">
                                        {{ $generated['statement_count'] }} flat{{ $generated['statement_count'] === 1 ? '' : 's' }}
                                    </span>
                                </span>
                                <span class="tree-amount fw-semibold">৳{{ number_format($generated['total'], 2) }}</span>
                            </summary>

                            <ul class="tree-children">
                                @forelse($generated['heads'] as $head)
                                    <li>
                                        <details class="head-details">
                                            <summary class="head-row">
                                                <span class="tree-label fw-semibold">{{ $head['label'] }}</span>
                                                <span class="tree-amount">
                                                    <span class="text-muted small me-2">{{ $head['line_count'] }} line{{ $head['line_count'] === 1 ? '' : 's' }}</span>
                                                    <span class="fw-semibold">৳{{ number_format($head['total'], 2) }}</span>
                                                </span>
                                            </summary>
                                            <ul class="flat-children">
                                                @foreach($head['flats'] as $flatLine)
                                                    <li class="flat-row">
                                                        <span class="tree-label">
                                                            {{ $flatLine['flat'] }}
                                                            @if($flatLine['note'])
                                                                <span class="text-muted">· {{ $flatLine['note'] }}</span>
                                                            @endif
                                                        </span>
                                                        <span class="tree-amount">৳{{ number_format($flatLine['amount'], 2) }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    </li>
                                @empty
                                    <li class="text-muted">No enabled bill lines for this month.</li>
                                @endforelse
                                <li class="head-row fw-semibold">
                                    <span class="tree-label">Month total</span>
                                    <span class="tree-amount">৳{{ number_format($generated['total'], 2) }}</span>
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
@endsection
