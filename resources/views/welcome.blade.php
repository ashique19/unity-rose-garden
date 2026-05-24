@extends('layouts.layout')
@section('content')

<div class="features-section pt-20 pb-20">
    <div class="container">
        
        <div class="mb-5 text-center text-md-start d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
            <div>
                <h1 class="fw-bold text-dark mb-1">Unity Rose Garden</h1>
                <p class="text-muted fs-5 mb-0">LPG Centralized Metering Dashboard</p>
            </div>
            <div>
                <a href="/bill-history" class="btn btn-outline-secondary px-4 py-2">
                    📁 View Bill History Archive
                </a>
            </div>
        </div>

        @if($latestBill)
            <div class="fv-card mb-4 border border-success-subtle shadow-sm">
                <div class="fv-card-label bg-success text-white d-flex justify-content-between align-items-center">
                    <span>Latest Billing Summary ({{ $latestBill->bill_for_month->format('F Y') }})</span>
                    <span class="badge bg-white text-success fs-6 fw-bold">Active Statement</span>
                </div>
                
                <div class="p-4 bg-white rounded-bottom">
                    <div class="row g-4 text-center text-md-start">
                        <div class="col-md-3">
                            <span class="text-muted d-block mb-1">Total Bill Collected</span>
                            <h3 class="fw-bold text-dark mb-0">{{ number_format($latestBill->total_bill, 2) }} Tk</h3>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block mb-1">Total LPG Consumption</span>
                            <h3 class="fw-bold text-dark mb-0">{{ number_format($latestBill->total_used_m3, 2) }} m³</h3>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block mb-1">Total Converted Mass</span>
                            <h3 class="fw-bold text-dark mb-0">{{ number_format($latestBill->total_used_kg, 2) }} kg</h3>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block mb-1">Calculated Price Rate</span>
                            <h3 class="fw-bold text-success mb-0">{{ number_format($latestBill->price_per_kg, 2) }} Tk/kg</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fv-card shadow-sm">
                <div class="fv-card-label">Flat-wise Breakdown Details</div>
                <div class="table-responsive bg-white rounded-bottom border border-top-0" style="padding: 10px 0;">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4 text-secondary text-uppercase fs-7">Flat Info</th>
                                <th class="text-secondary text-uppercase fs-7">Prev Reading</th>
                                <th class="text-secondary text-uppercase fs-7">Current Reading</th>
                                <th class="text-secondary text-uppercase fs-7">Used (m³)</th>
                                <th class="text-secondary text-uppercase fs-7">Used (kg)</th>
                                <th class="pe-4 text-end text-secondary text-uppercase fs-7">Amount Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($latestBill->details as $detail)
                                @php
                                    // Math formula linking usage weight to the dynamically shared building cost
                                    $flatAmountDue = $detail->used_kg * $latestBill->price_per_kg;
                                @endphp
                                <tr>
                                    <td class="ps-4 font-weight-medium text-dark">
                                        Flat {{ $detail->flat->name ?? $detail->flat_id }}
                                    </td>
                                    <td class="text-muted">{{ number_format($detail->previous_reading, 2) }}</td>
                                    <td class="text-muted">{{ number_format($detail->current_reading, 2) }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1.5 fw-normal fs-6">
                                            {{ number_format($detail->used_m3, 2) }} m³
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1.5 fw-normal fs-6">
                                            {{ number_format($detail->used_kg, 2) }} kg
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end fw-bold text-dark fs-6">
                                        {{ number_format($flatAmountDue, 2) }} Tk
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="fv-card border border-dashed text-center py-5 shadow-sm">
                <div class="p-4">
                    <h4 class="fw-semibold text-secondary">No Billings Found</h4>
                    <p class="text-muted max-w-md mx-auto mb-4">Get started by navigating to the bill engine to log metrics and generate the building's initial statements.</p>
                </div>
            </div>
        @endif

    </div>
</div>

@stop