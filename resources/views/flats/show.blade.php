@extends('layouts.layout')
@section('content')

<div class="container pt-5 pb-5 mt-20">
    
    <div class="mb-3">
        <a href="{{ route('flats.index') }}" class="text-decoration-none text-secondary">← Back to Directory</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Consumption History: Flat {{ $flat->name }}</h2>
            <p class="text-muted mb-0">Showing up to the latest 12 statements for this unit.</p>
        </div>
        <div>
            @if($flat->status === 'online')
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded">Status : Online</span>
            @else
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded">Status : Offline</span>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Billing Month</th>
                        <th>Previous Reading</th>
                        <th>Current Reading</th>
                        <th>Consumed (m³)</th>
                        <th>Converted Mass (kg)</th>
                        <th>Price per (kg)</th>
                        <th class="pe-4 text-end">Amount Due</th>
                    </tr>
                </thead>
                <tbody>
                    @if($billingHistory->isEmpty())
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No billing records found for this flat yet.
                            </td>
                        </tr>
                    @else
                        @foreach($billingHistory as $statement)
                        <tr>
                            <td class="ps-4 font-weight-medium">
                                {{ \Carbon\Carbon::parse($statement->bill_for_month)->format('F Y') }}
                            </td>
                            <td>{{ number_format($statement->previous_reading, 2) }}</td>
                            <td>{{ number_format($statement->current_reading, 2) }}</td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    {{ number_format($statement->used_m3, 2) }} m³
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                    {{ number_format($statement->used_kg, 2) }} kg
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                    {{ $statement->bill->price_per_kg }} Tk/kg
                                </span>
                            </td>
                            <td class="pe-4 text-end font-weight-bold text-dark">
                                {{ number_format($statement->used_kg * $statement->bill->price_per_kg, 2) }} Tk
                            </td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

@stop