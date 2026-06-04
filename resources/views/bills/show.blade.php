@extends('layouts.layout')
@section('content')

<div class="features-section pt-20 pb-20">
    <div class="container">
        
        <div class="mb-3">
            <a href="/bill-history" class="text-decoration-none text-secondary">← Back to History</a>
        </div>

        @php
            // Dynamically calculate the building-wide outstanding due sum for this statement month
            $totalPendingDue = $bill->details->where('payment_status', 'unpaid')->sum('amount_due');
        @endphp

        <div class="fv-card mb-4 border-start border-primary border-4 shadow-sm">
            <div class="fv-card-label bg-light text-dark font-weight-bold border-bottom py-2.5 ps-4">
                {{ $bill->name }}
            </div>
            <div class="p-4 bg-white rounded-bottom border border-top-0">
                <div class="row g-3 text-center text-md-start">
                    
                    <div class="col-md-3 border-end border-light border-2">
                        <small class="text-muted d-block text-uppercase tracking-wider mb-1" style="font-size: 10px; font-weight: 700;">Total Building Bill</small>
                        <strong class="fs-4 text-dark">{{ number_format($bill->total_bill, 2) }} Tk</strong>
                    </div>
                    
                    <div class="col-md-3 border-end border-light border-2">
                        <small class="text-muted d-block text-uppercase tracking-wider mb-1" style="font-size: 10px; font-weight: 700;">Total Pending Due</small>
                        <strong class="fs-4 {{ $totalPendingDue > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($totalPendingDue, 2) }} Tk
                        </strong>
                    </div>
                    
                    <div class="col-md-3 border-end border-light border-2">
                        <small class="text-muted d-block text-uppercase tracking-wider mb-1" style="font-size: 10px; font-weight: 700;">Total Consumption Metrics</small>
                        <strong class="fs-5 text-dark d-block" style="line-height: 1.2;">
                            {{ number_format($bill->total_used_m3, 2) }} m³
                        </strong>
                        <small class="text-secondary" style="font-size: 11px;">Mass: {{ number_format($bill->total_used_kg, 2) }} kg</small>
                    </div>
                    
                    <div class="col-md-3">
                        <small class="text-muted d-block text-uppercase tracking-wider mb-1" style="font-size: 10px; font-weight: 700;">Calculated Rate / KG</small>
                        <strong class="fs-4 text-success">{{ number_format($bill->price_per_kg, 2) }} Tk</strong>
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
                            <th>Amount Due</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
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
                                    <a href="{{ route('flats.show', $detail->flat_id) }}" class="text-decoration-none font-weight-bold text-primary">
                                        Flat {{ $detail->flat->name ?? $detail->flat_id }} ↗
                                    </a>
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
                                <td>
                                    @if($detail->payment_status === 'paid')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1.5 rounded">
                                            🟢 Paid
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1.5 rounded">
                                            🟡 Unpaid
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        @auth
                                            <form action="{{ route('bill-details.toggle-payment', $detail->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $detail->payment_status === 'paid' ? 'btn-outline-warning' : 'btn-outline-success' }} px-2 py-1" style="font-size: 11px;">
                                                    Mark as {{ $detail->payment_status === 'paid' ? 'Unpaid' : 'Paid' }}
                                                </button>
                                            </form>
                                        @endauth

                                        <a href="{{ route('flats.bill.print', ['flat_id' => $detail->flat_id, 'bill_month' => $detail->bill_for_month]) }}" 
                                        target="_blank" 
                                        class="btn btn-sm btn-outline-secondary px-2 py-1"
                                        style="font-size: 11px;">
                                            📄 Print
                                        </a>
                                    </div>
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