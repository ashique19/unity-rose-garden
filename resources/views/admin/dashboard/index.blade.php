@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <h1 class="fw-bold text-dark mb-1">Accounts dashboard</h1>
        <p class="text-muted mb-4">{{ $building?->name ?? 'Unity Rose Garden' }} cashbook overview</p>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="bg-white border rounded-3 shadow-sm p-4 h-100">
                    <div class="text-muted small">Opening balance</div>
                    <div class="fw-bold fs-4">৳ {{ number_format($opening, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-white border rounded-3 shadow-sm p-4 h-100">
                    <div class="text-muted small">Total cash in</div>
                    <div class="fw-bold fs-4 text-success">৳ {{ number_format($cashIn, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-white border rounded-3 shadow-sm p-4 h-100">
                    <div class="text-muted small">Total cash out</div>
                    <div class="fw-bold fs-4 text-danger">৳ {{ number_format($cashOut, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-white border rounded-3 shadow-sm p-4 h-100">
                    <div class="text-muted small">Current balance</div>
                    <div class="fw-bold fs-4">৳ {{ number_format((float) $balance, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="bg-white border border-warning-subtle rounded-3 shadow-sm p-4 h-100">
                    <div class="text-muted small">Pending collections</div>
                    <div class="fw-bold fs-3">৳ {{ number_format((float) $pending, 2) }}</div>
                    <a href="{{ route('admin.collections.index') }}" class="small">Record payments →</a>
                </div>
            </div>
            <div class="col-md-8 d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('admin.ledger.index') }}" class="btn btn-outline-secondary">Open ledger</a>
                <a href="{{ route('admin.collections.index') }}" class="btn btn-outline-secondary">Collections</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="bg-white border rounded-3 shadow-sm p-4">
                    <h2 class="h5 fw-bold mb-3">Top pending statements</h2>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Flat</th>
                                    <th>Month</th>
                                    <th class="text-end">Pending</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingStatements as $statement)
                                    <tr>
                                        <td>{{ $statement->flat?->name }}</td>
                                        <td>{{ $statement->bill_month?->format('M Y') }}</td>
                                        <td class="text-end">{{ number_format((float) $statement->pendingAmount(), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted text-center">Nothing pending.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="bg-white border rounded-3 shadow-sm p-4">
                    <h2 class="h5 fw-bold mb-3">Recent ledger</h2>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentEntries as $entry)
                                    <tr>
                                        <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                                        <td>{{ $entry->type === 'cash_in' ? 'In' : 'Out' }}{{ $entry->flat ? ' · '.$entry->flat->name : '' }}</td>
                                        <td class="text-end">{{ number_format((float) $entry->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted text-center">No entries yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
