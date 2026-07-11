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
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <h2 class="h5 fw-bold mb-0">Add collection</h2>
                <div class="d-flex flex-wrap gap-3 small" id="collection-balance-preview"
                     data-balance="{{ number_format($availableBalance, 2, '.', '') }}">
                    <div>
                        <span class="text-muted">Available before</span>
                        <div class="fw-semibold">৳<span id="collection-balance-before">{{ number_format($availableBalance, 2) }}</span></div>
                    </div>
                    <div>
                        <span class="text-muted">After this collection</span>
                        <div class="fw-semibold">৳<span id="collection-balance-after">{{ number_format($availableBalance, 2) }}</span></div>
                    </div>
                </div>
            </div>
            <form method="post" action="{{ route('admin.collections.store') }}" class="row g-3" id="collection-create-form">
                @csrf
                <div class="col-md-4">
                    <label class="form-label" for="collection-statement">Statement (flat)</label>
                    <select name="monthly_statement_id" id="collection-statement" class="form-select" required @disabled($statements->isEmpty())>
                        <option value="" data-pending="">{{ $statements->isEmpty() ? 'No statements this month — generate first' : 'Select flat…' }}</option>
                        @foreach($statements as $statement)
                            @php $pending = (float) $statement->pendingAmount(); @endphp
                            <option value="{{ $statement->id }}" data-pending="{{ number_format($pending, 2, '.', '') }}">
                                {{ $statement->flat?->name ?? 'Flat #'.$statement->flat_id }}
                                — pending ৳{{ number_format($pending, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="collection-amount">Amount (৳)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="collection-amount" class="form-control" required>
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
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Before</th>
                        <th class="text-end">After</th>
                        <th>Note</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statements as $statement)
                        @php $collections = $statement->collections; @endphp
                        @if($collections->isEmpty())
                            <tr>
                                <td class="fw-semibold">{{ $statement->flat?->name }}</td>
                                <td class="text-end">{{ number_format((float) $statement->totalAmount(), 2) }}</td>
                                <td class="text-end">{{ number_format((float) $statement->collectedAmount(), 2) }}</td>
                                <td class="text-end">{{ number_format((float) $statement->pendingAmount(), 2) }}</td>
                                <td colspan="5" class="text-muted">No payments yet</td>
                                <td></td>
                            </tr>
                        @else
                            @foreach($collections as $index => $collection)
                                <tr>
                                    @if($index === 0)
                                        <td class="fw-semibold" rowspan="{{ $collections->count() }}">{{ $statement->flat?->name }}</td>
                                        <td class="text-end" rowspan="{{ $collections->count() }}">{{ number_format((float) $statement->totalAmount(), 2) }}</td>
                                        <td class="text-end" rowspan="{{ $collections->count() }}">{{ number_format((float) $statement->collectedAmount(), 2) }}</td>
                                        <td class="text-end" rowspan="{{ $collections->count() }}">{{ number_format((float) $statement->pendingAmount(), 2) }}</td>
                                    @endif
                                    <td>{{ $collection->collected_on?->format('d M Y') ?? '—' }}</td>
                                    <td class="text-end">{{ number_format((float) $collection->amount, 2) }}</td>
                                    <td class="text-end">
                                        {{ $collection->balance_before !== null ? number_format((float) $collection->balance_before, 2) : '—' }}
                                    </td>
                                    <td class="text-end">
                                        {{ $collection->balance_after !== null ? number_format((float) $collection->balance_after, 2) : '—' }}
                                    </td>
                                    <td>{{ $collection->note ?: '—' }}</td>
                                    <td class="text-nowrap">
                                        <form method="post" action="{{ route('admin.collections.destroy', $collection) }}"
                                              onsubmit="return confirm('Remove this collection?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
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

@section('js')
<script>
(() => {
  const select = document.getElementById('collection-statement');
  const amount = document.getElementById('collection-amount');
  if (!select || !amount) return;

  const preview = document.getElementById('collection-balance-preview');
  const postCheckbox = document.getElementById('post_to_ledger');
  const afterEl = document.getElementById('collection-balance-after');
  const before = preview ? parseFloat(preview.dataset.balance || '0') : 0;

  function formatMoney(value) {
    return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function updateAfter() {
    if (!afterEl || !postCheckbox) return;
    const value = parseFloat(amount.value || '0');
    const hitLedger = postCheckbox.checked && !Number.isNaN(value) && value > 0;
    afterEl.textContent = formatMoney(hitLedger ? before + value : before);
  }

  select.addEventListener('change', () => {
    const pending = select.selectedOptions[0]?.dataset?.pending ?? '';
    if (pending === '' || Number(pending) <= 0) {
      amount.value = '';
      updateAfter();
      return;
    }
    amount.value = pending;
    amount.focus();
    amount.select();
    updateAfter();
  });

  amount.addEventListener('input', updateAfter);
  postCheckbox?.addEventListener('change', updateAfter);
  updateAfter();
})();
</script>
@endsection
