@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <h1 class="fw-bold text-dark mb-1">Payees</h1>
        <p class="text-muted mb-4">
            Register vendors / payees used on the ledger. Active payees appear in the ledger Payee dropdown.
            Deactivate instead of deleting payees that are already used.
        </p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <x-mobile-panel-toggles :add-open="$errors->any()">
            <x-slot:add>
                <div class="bg-white border rounded-3 shadow-sm p-4 mb-4">
                    <h2 class="h5 fw-bold mb-3">Add / Register payee</h2>
                    <form method="post" action="{{ route('admin.payees.store') }}" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required maxlength="120"
                                   placeholder="e.g. WASA">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Phone (optional)</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" maxlength="20">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Note (optional)</label>
                            <input type="text" name="note" class="form-control" value="{{ old('note') }}" maxlength="255">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Sort</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order') }}" min="0">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-primary w-100">Add</button>
                        </div>
                    </form>
                </div>
            </x-slot:add>
        </x-mobile-panel-toggles>

        <div class="table-responsive bg-white border rounded-3 shadow-sm">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Note</th>
                        <th>Sort</th>
                        <th>Active</th>
                        <th>Used</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vendors as $vendor)
                        <tr>
                            <form method="post" action="{{ route('admin.payees.update', $vendor) }}" id="payee-{{ $vendor->id }}">
                                @csrf
                                @method('PUT')
                            </form>
                            <td>
                                <input form="payee-{{ $vendor->id }}" type="text" name="name" class="form-control form-control-sm"
                                       value="{{ $vendor->name }}" required maxlength="120">
                            </td>
                            <td>
                                <input form="payee-{{ $vendor->id }}" type="text" name="phone" class="form-control form-control-sm"
                                       value="{{ $vendor->phone }}" maxlength="20">
                            </td>
                            <td>
                                <input form="payee-{{ $vendor->id }}" type="text" name="note" class="form-control form-control-sm"
                                       value="{{ $vendor->note }}" maxlength="255">
                            </td>
                            <td style="max-width: 5rem;">
                                <input form="payee-{{ $vendor->id }}" type="number" name="sort_order" class="form-control form-control-sm"
                                       value="{{ $vendor->sort_order }}" min="0" required>
                            </td>
                            <td>
                                <div class="form-check">
                                    <input form="payee-{{ $vendor->id }}" class="form-check-input" type="checkbox"
                                           name="is_active" value="1" id="active-{{ $vendor->id }}"
                                           @checked($vendor->is_active)>
                                    <label class="form-check-label" for="active-{{ $vendor->id }}">Active</label>
                                </div>
                            </td>
                            <td class="text-muted small">{{ $vendor->ledger_entries_count }}</td>
                            <td class="text-nowrap">
                                <button form="payee-{{ $vendor->id }}" class="btn btn-sm btn-outline-primary">Save</button>
                                @if($vendor->ledger_entries_count === 0)
                                    <form method="post" action="{{ route('admin.payees.destroy', $vendor) }}" class="d-inline"
                                          onsubmit="return confirm('Delete payee {{ $vendor->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No payees yet. Register one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
