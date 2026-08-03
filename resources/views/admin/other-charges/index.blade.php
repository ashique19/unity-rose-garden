@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold text-dark mb-1">Other charges</h1>
                <p class="text-muted mb-0">Ad-hoc / template overrides for the selected month. Building-wide templates apply on generate if no override exists.</p>
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
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-mobile-panel-toggles :add-open="$errors->any()">
            <x-slot:add>
                <div class="bg-white border rounded-3 shadow-sm p-4 mb-4">
                    <h2 class="h5 fw-bold mb-3">Add charge</h2>
                    <form method="post" action="{{ route('admin.other-charges.store') }}" class="row g-3">
                        @csrf
                        <input type="hidden" name="charge_month" value="{{ $selectedMonth->format('Y-m') }}">
                        <div class="col-md-3">
                            <label class="form-label">Flat</label>
                            <select name="flat_id" class="form-select" required>
                                <option value="">Select…</option>
                                @foreach($flats as $flat)
                                    <option value="{{ $flat->id }}" @selected(old('flat_id') == $flat->id)>{{ $flat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bill type</label>
                            <select name="bill_type_id" id="bill_type_id" class="form-select" required>
                                <option value="">Select…</option>
                                @foreach($billTypes as $type)
                                    <option value="{{ $type->id }}" data-label="{{ $type->label }}" @selected(old('bill_type_id') == $type->id)>
                                        {{ $type->label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Label</label>
                            <input type="text" name="label" id="charge_label" class="form-control" value="{{ old('label') }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Amount (৳)</label>
                            <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount') }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
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
                        <th>Flat</th>
                        <th>Type</th>
                        <th>Label</th>
                        <th class="text-end">Amount</th>
                        <th>Notes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($charges as $charge)
                        <tr>
                            <td>{{ $charge->flat?->name }}</td>
                            <td>{{ $charge->billType?->label ?? '—' }}</td>
                            <td>{{ $charge->label }}</td>
                            <td class="text-end">{{ number_format((float) $charge->amount, 2) }}</td>
                            <td class="text-muted">{{ $charge->notes ?: '—' }}</td>
                            <td>
                                <form method="post" action="{{ route('admin.other-charges.destroy', $charge) }}"
                                      onsubmit="return confirm('Remove this charge?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No ad-hoc charges for this month.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.getElementById('bill_type_id')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const label = document.getElementById('charge_label');
    if (label && !label.value && opt?.dataset?.label) {
        label.value = opt.dataset.label;
    }
});
</script>
@endsection
