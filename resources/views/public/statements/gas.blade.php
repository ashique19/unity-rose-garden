@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <a href="{{ route('public.flats.show', ['flat' => $flat, 'month' => $selectedMonth->format('Y-m')]) }}"
           class="text-muted text-decoration-none small">&larr; Flat {{ $flat->name }}</a>

        <h1 class="fw-bold text-dark mt-2 mb-1">Gas bill details</h1>
        <p class="text-muted">{{ $flat->name }} · {{ $selectedMonth->format('F Y') }}</p>

        @if(!$gasLine)
            <div class="border rounded-3 p-5 bg-white text-center text-muted">
                No gas line for this month.
            </div>
        @else
            @php $meta = $gasLine->meta ?? []; @endphp
            <div class="table-responsive bg-white border rounded-3 shadow-sm">
                <table class="table mb-0">
                    <tbody>
                        <tr>
                            <th class="w-50">Reading date</th>
                            <td>{{ isset($meta['reading_date']) ? \Carbon\Carbon::parse($meta['reading_date'])->format('d M Y') : '—' }}</td>
                        </tr>
                        <tr>
                            <th>Previous (m³)</th>
                            <td>{{ number_format((float) ($meta['previous_m3'] ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <th>Current (m³)</th>
                            <td>{{ number_format((float) ($meta['current_m3'] ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <th>Consumed (m³)</th>
                            <td>{{ number_format((float) ($meta['consumed_m3'] ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <th>Consumed (kg)</th>
                            <td>{{ number_format((float) ($meta['consumed_kg'] ?? $gasLine->quantity ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <th>Rate / kg</th>
                            <td>{{ number_format((float) ($meta['rate_per_kg'] ?? $gasLine->rate ?? 0), 2) }}</td>
                        </tr>
                        <tr class="fw-bold">
                            <th>Total</th>
                            <td>৳ {{ number_format((float) $gasLine->amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
