@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <h1 class="fw-bold text-dark mb-1">Accounts dashboard</h1>
        <p class="text-muted mb-4">{{ $building?->name ?? 'Unity Rose Garden' }} cashbook overview</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="bg-white border rounded-3 shadow-sm p-4 h-100">
                    <div class="text-muted small">Opening balance</div>
                    <div class="fw-bold fs-4">৳ {{ number_format($opening, 2) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-white border rounded-3 shadow-sm p-4 h-100">
                    <div class="text-muted small">Current balance</div>
                    <div class="fw-bold fs-4">৳ {{ number_format((float) $balance, 2) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-white border border-warning-subtle rounded-3 shadow-sm p-4 h-100">
                    <div class="text-muted small">Pending total</div>
                    <div class="fw-bold fs-4">৳ {{ number_format((float) $pending, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
            <a href="{{ route('admin.ledger.index') }}" class="btn btn-outline-secondary">Open ledger</a>
            <a href="{{ route('admin.collections.index') }}" class="btn btn-outline-secondary">Collections</a>
            <a href="{{ route('public.statements.print-building', ['month' => $printMonth]) }}" class="btn btn-outline-secondary">Print bills</a>
            <a href="{{ route('public.statements.print-building-pos', ['month' => $printMonth]) }}" class="btn btn-outline-secondary">POS print</a>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="bg-white border rounded-3 shadow-sm p-4">
                    <h2 class="h5 fw-bold mb-3">Pending collections</h2>
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Flat</th>
                                    <th class="text-end">Amount</th>
                                    <th style="width: 1%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingStatements as $statement)
                                    @php
                                        $amount = (float) $statement->pendingAmount();
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $statement->flat?->name }}</div>
                                            <div class="text-muted small">{{ $statement->bill_month?->format('M Y') }}</div>
                                        </td>
                                        <td class="text-end fw-semibold">৳ {{ number_format($amount, 2) }}</td>
                                        <td class="text-nowrap">
                                            <form method="post" action="{{ route('admin.collections.store') }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="monthly_statement_id" value="{{ $statement->id }}">
                                                <input type="hidden" name="amount" value="{{ number_format($amount, 2, '.', '') }}">
                                                <input type="hidden" name="collected_on" value="{{ now()->toDateString() }}">
                                                <input type="hidden" name="post_to_ledger" value="1">
                                                <input type="hidden" name="redirect_to" value="dashboard">
                                                <button type="submit" class="btn btn-sm btn-primary">Receive</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted text-center">Nothing pending.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
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
