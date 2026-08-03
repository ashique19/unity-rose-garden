@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <h1 class="fw-bold text-dark mb-1">Expense heads</h1>
        <p class="text-muted mb-4">Create expense types first (salary, repair, supplies, …). Deactivate instead of deleting heads that are in use.</p>

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
                    <h2 class="h5 fw-bold mb-3">Add head</h2>
                    <form method="post" action="{{ route('admin.expense-heads.store') }}" class="row g-3">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label">Label</label>
                            <input type="text" name="label" class="form-control" value="{{ old('label') }}" required maxlength="120">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Key (optional)</label>
                            <input type="text" name="key" class="form-control" value="{{ old('key') }}" maxlength="80" placeholder="auto from label">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Sort</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order') }}" min="0">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
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
                        <th>Label</th>
                        <th>Key</th>
                        <th>Sort</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($heads as $head)
                        <tr>
                            <form method="post" action="{{ route('admin.expense-heads.update', $head) }}" id="head-{{ $head->id }}">
                                @csrf
                                @method('PUT')
                            </form>
                            <td>
                                <input form="head-{{ $head->id }}" type="text" name="label" class="form-control form-control-sm"
                                       value="{{ $head->label }}" required>
                            </td>
                            <td><code>{{ $head->key }}</code></td>
                            <td style="max-width: 6rem;">
                                <input form="head-{{ $head->id }}" type="number" name="sort_order" class="form-control form-control-sm"
                                       value="{{ $head->sort_order }}" min="0" required>
                            </td>
                            <td>
                                <div class="form-check">
                                    <input form="head-{{ $head->id }}" class="form-check-input" type="checkbox"
                                           name="is_active" value="1" id="active-{{ $head->id }}"
                                           @checked($head->is_active)>
                                    <label class="form-check-label" for="active-{{ $head->id }}">Active</label>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <button form="head-{{ $head->id }}" class="btn btn-sm btn-outline-primary">Save</button>
                                @if($head->expenses_count === 0 && $head->ledger_entries_count === 0)
                                    <form method="post" action="{{ route('admin.expense-heads.destroy', $head) }}" class="d-inline"
                                          onsubmit="return confirm('Delete head {{ $head->label }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
