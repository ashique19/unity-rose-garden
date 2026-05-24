@extends('layouts.layout')
@section('content')

<div class="features-section pt-20 pb-20">
    <div class="container">
        
        <div class="mb-3">
            <a href="/bill-history" class="text-decoration-none text-secondary">← Back to History</a>
        </div>

        <div class="fv-card mb-4">
            <div class="fv-card-label">{{ $bill->name }}</div>
            <div class="p-4 bg-white rounded-bottom border border-top-0">
                <div class="row g-3">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Total Building Bill</small>
                        <strong class="fs-5 text-dark">{{ number_format($bill->total_bill, 2) }} Tk</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Total Gas Consumed</small>
                        <strong class="fs-5 text-dark">{{ number_format($bill->total_used_m3, 2) }} m³</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Total Weight Conversion</small>
                        <strong class="fs-5 text-dark">{{ number_format($bill->total_used_kg, 2) }} kg</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Calculated Rate / KG</small>
                        <strong class="fs-5 text-success">{{ number_format($bill->price_per_kg, 2) }} Tk</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="fv-card">
            <div class="fv-card-label">Flat-wise Consumption Breakdowns</div>
            <div class="table-responsive style="margin-top: 8px;">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">Flat Info</th>
                            <th>Previous Reading</th>
                            <th>Current Reading</th>
                            <th>Used Unit (m³)</th>
                            <th>Used Mass (kg)</th>
                            <th class="pe-4 text-end">Amount Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bill->details as $detail)
                            @php
                                // Dynamic formula: Flat's KG usage multiplied by building rate per KG
                                $flatAmountDue = $detail->used_kg * $bill->price_per_kg;
                            @endphp
                            <tr>
                                <td class="ps-4 font-weight-medium">
                                    Flat {{ $detail->flat->name ?? $detail->flat_id }}
                                </td>
                                <td>{{ number_format($detail->previous_reading, 2) }}</td>
                                <td>{{ number_format($detail->current_reading, 2) }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        {{ number_format($detail->used_m3, 2) }} m³
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                        {{ number_format($detail->used_kg, 2) }} kg
                                    </span>
                                </td>
                                <td class="pe-4 text-end font-weight-bold text-dark">
                                    {{ number_format($flatAmountDue, 2) }} Tk
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

@stop