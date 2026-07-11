@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <a href="{{ route('public.flats.show', ['flat' => $flat, 'month' => $selectedMonth->format('Y-m')]) }}"
           class="text-muted text-decoration-none small">&larr; Flat {{ $flat->name }}</a>

        <h1 class="fw-bold text-dark mt-2 mb-1">Other bill details</h1>
        <p class="text-muted">{{ $flat->name }} · {{ $selectedMonth->format('F Y') }}</p>

        @if($otherLines->isEmpty())
            <div class="border rounded-3 p-5 bg-white text-center text-muted">
                No other charges for this month.
            </div>
        @else
            <div class="table-responsive bg-white border rounded-3 shadow-sm">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Heading</th>
                            <th>Note</th>
                            <th class="text-end">Amount (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($otherLines as $line)
                            <tr>
                                <td>{{ $line->label }}</td>
                                <td class="text-muted">{{ $line->note ?: '—' }}</td>
                                <td class="text-end">{{ number_format((float) $line->amount, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="fw-bold">
                            <td colspan="2">Subtotal</td>
                            <td class="text-end">{{ number_format($otherLines->sum(fn ($l) => (float) $l->amount), 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
