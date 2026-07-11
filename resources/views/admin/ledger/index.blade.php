@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold text-dark mb-1">Cashbook ledger</h1>
                <p class="text-muted mb-0">Cash in (flat optional) and cash out.</p>
            </div>
            <form method="get" class="d-flex align-items-center gap-2">
                <label for="type" class="form-label mb-0">Filter</label>
                <select name="type" id="type" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="cash_in" @selected($filterType === 'cash_in')>Cash in</option>
                    <option value="cash_out" @selected($filterType === 'cash_out')>Cash out</option>
                </select>
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
            <h2 class="h5 fw-bold mb-3">Add entry</h2>
            <form method="post" action="{{ route('admin.ledger.store') }}" class="row g-3">
                @csrf
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="cash_in">Cash in</option>
                        <option value="cash_out">Cash out</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Amount (৳)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" name="entry_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Flat (optional)</label>
                    <select name="flat_id" class="form-select">
                        <option value="">None</option>
                        @foreach($flats as $flat)
                            <option value="{{ $flat->id }}">{{ $flat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">—</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}">{{ ucfirst($category) }}</option>
                        @endforeach
                        <option value="donation">Donation</option>
                        <option value="collection">Collection</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Note</label>
                    <input type="text" name="note" class="form-control">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Save entry</button>
                </div>
            </form>
        </div>

        <div class="table-responsive bg-white border rounded-3 shadow-sm">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Flat</th>
                        <th>Category</th>
                        <th>Note</th>
                        <th class="text-end">Amount</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                            <td>
                                @if($entry->type === 'cash_in')
                                    <span class="badge text-bg-success">Cash in</span>
                                @else
                                    <span class="badge text-bg-danger">Cash out</span>
                                @endif
                            </td>
                            <td>{{ $entry->flat?->name ?? '—' }}</td>
                            <td>{{ $entry->category ? ucfirst($entry->category) : '—' }}</td>
                            <td class="text-muted">{{ $entry->note ?: '—' }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $entry->amount, 2) }}</td>
                            <td>
                                @unless($entry->collection_id)
                                    <form method="post" action="{{ route('admin.ledger.destroy', $entry) }}"
                                          onsubmit="return confirm('Remove this entry?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Del</button>
                                    </form>
                                @else
                                    <span class="text-muted small">via collection</span>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No ledger entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $entries->links() }}</div>
    </div>
</div>
@endsection
