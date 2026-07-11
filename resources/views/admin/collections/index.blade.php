@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold text-dark mb-1">Collections</h1>
                <p class="text-muted mb-0">Record multiple payments per flat statement.</p>
            </div>
            <form method="get" class="d-flex align-items-center gap-2">
                <label for="month" class="form-label mb-0">Month</label>
                <input type="month" name="month" id="month" class="form-control"
                       value="{{ $selectedMonth->format('Y-m') }}" onchange="this.form.submit()">
            </form>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="bg-white border rounded-3 shadow-sm p-4 mb-4">
            <h2 class="h5 fw-bold mb-3">Add collection</h2>
            <form method="post" action="{{ route('admin.collections.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Statement (flat)</label>
                    <select name="monthly_statement_id" class="form-select" required @disabled($statements->isEmpty())>
                        <option value="">{{ $statements->isEmpty() ? 'No statements this month — generate first' : 'Select flat…' }}</option>
                        @foreach($statements as $statement)
                            <option value="{{ $statement->id }}">
                                {{ $statement->flat?->name ?? 'Flat #'.$statement->flat_id }}
                                — pending ৳{{ number_format((float) $statement->pendingAmount(), 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Amount (৳)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Collected on</label>
                    <input type="date" name="collected_on" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Note</label>
                    <input type="text" name="note" class="form-control">
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="post_to_ledger" value="1" id="post_to_ledger" checked>
                        <label class="form-check-label" for="post_to_ledger">Also post cash-in to ledger</label>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <button class="btn btn-primary">Record collection</button>
                </div>
            </form>
        </div>

        <div class="table-responsive bg-white border rounded-3 shadow-sm">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Flat</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Collected</th>
                        <th class="text-end">Pending</th>
                        <th>Payments</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statements as $statement)
                        <tr>
                            <td class="fw-semibold">{{ $statement->flat?->name }}</td>
                            <td class="text-end">{{ number_format((float) $statement->totalAmount(), 2) }}</td>
                            <td class="text-end">{{ number_format((float) $statement->collectedAmount(), 2) }}</td>
                            <td class="text-end">{{ number_format((float) $statement->pendingAmount(), 2) }}</td>
                            <td>
                                @if($statement->collections->isEmpty())
                                    <span class="text-muted">—</span>
                                @else
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach($statement->collections as $collection)
                                            <li class="d-flex justify-content-between gap-2 align-items-center">
                                                <span>
                                                    ৳{{ number_format((float) $collection->amount, 2) }}
                                                    on {{ $collection->collected_on?->format('d M Y') }}
                                                    @if($collection->note)
                                                        <span class="text-muted">({{ $collection->note }})</span>
                                                    @endif
                                                </span>
                                                <form method="post" action="{{ route('admin.collections.destroy', $collection) }}"
                                                      onsubmit="return confirm('Remove this collection?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-link text-danger p-0">Remove</button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No statements for this month. Generate the month first.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
