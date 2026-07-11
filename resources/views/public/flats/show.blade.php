@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
            <div>
                <a href="{{ route('home') }}" class="text-muted text-decoration-none small">&larr; All flats</a>
                <h1 class="fw-bold text-dark mb-1 mt-2">Flat {{ $flat->name }}</h1>
                <p class="text-muted mb-0">Monthly statement summary</p>
            </div>

            <form method="get" class="d-flex align-items-center gap-2">
                <label for="month" class="form-label mb-0 text-nowrap">Month</label>
                <select name="month" id="month" class="form-select" onchange="this.form.submit()">
                    @php
                        $currentKey = $selectedMonth->format('Y-m');
                        $options = $availableMonths->push($currentKey)->unique()->sortDesc()->values();
                    @endphp
                    @foreach($options as $option)
                        <option value="{{ $option }}" @selected($option === $currentKey)>
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $option)->format('F Y') }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        @if(!$statement)
            <div class="border rounded-3 p-5 bg-white text-center text-muted">
                No statement for {{ $selectedMonth->format('F Y') }}.
            </div>
        @else
            <div class="table-responsive bg-white border rounded-3 shadow-sm">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Charge</th>
                            <th class="text-end">Amount (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td class="text-end">{{ number_format($total, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Collected</td>
                            <td class="text-end text-muted">{{ number_format((float) $statement->collectedAmount(), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Pending</td>
                            <td class="text-end text-muted">{{ number_format((float) $statement->pendingAmount(), 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <a href="{{ route('public.statements.print', ['flat' => $flat, 'month' => $selectedMonth->format('Y-m')]) }}"
                   class="btn btn-primary">Print / PDF</a>
                <a href="{{ route('public.statements.gas', ['flat' => $flat, 'month' => $selectedMonth->format('Y-m')]) }}"
                   class="btn btn-outline-secondary">Gas bill details</a>
                <a href="{{ route('public.statements.others', ['flat' => $flat, 'month' => $selectedMonth->format('Y-m')]) }}"
                   class="btn btn-outline-secondary">Other bill details</a>
            </div>
        @endif
    </div>
</div>
@endsection
