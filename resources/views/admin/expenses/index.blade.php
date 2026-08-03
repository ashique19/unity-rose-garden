@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold text-dark mb-1">Expenses / purchases</h1>
                <p class="text-muted mb-0">Record association money out by expense head. Notes are required.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.expense-heads.index') }}" class="btn btn-outline-secondary btn-sm">Expense heads</a>
                <a href="{{ route('admin.payees.index') }}" class="btn btn-outline-secondary btn-sm">Payees</a>
                <a href="{{ route('admin.expenses.print-list', request()->query()) }}" class="btn btn-outline-primary btn-sm" target="_blank">Print list</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <x-mobile-panel-toggles :search-open="request()->hasAny(['from', 'to', 'head', 'month'])" :add-open="$errors->any()">
            <x-slot:search>
                <form method="get" class="row g-2 align-items-end bg-white border rounded-3 shadow-sm p-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">From</label>
                        <input type="date" name="from" class="form-control" value="{{ $from }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To</label>
                        <input type="date" name="to" class="form-control" value="{{ $to }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Head</label>
                        <select name="head" class="form-select">
                            <option value="">All heads</option>
                            @foreach($heads as $head)
                                <option value="{{ $head->id }}" @selected((string) $selectedHeadId === (string) $head->id)>
                                    {{ $head->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-primary w-100">Filter</button>
                    </div>
                </form>
            </x-slot:search>

            <x-slot:add>
        <div class="bg-white border rounded-3 shadow-sm p-4 mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                <h2 class="h5 fw-bold mb-0">Add expense</h2>
                <div class="d-flex flex-wrap gap-3 small" id="expense-balance-preview"
                     data-balance="{{ number_format($availableBalance, 2, '.', '') }}">
                    <div>
                        <span class="text-muted">Available before</span>
                        <div class="fw-semibold">৳<span id="balance-before">{{ number_format($availableBalance, 2) }}</span></div>
                    </div>
                    <div>
                        <span class="text-muted">After this expense</span>
                        <div class="fw-semibold">৳<span id="balance-after">{{ number_format($availableBalance, 2) }}</span></div>
                    </div>
                </div>
            </div>
            <form method="post" action="{{ route('admin.expenses.store') }}" class="row g-3" id="expense-create-form">
                @csrf
                <input type="hidden" name="from" value="{{ $from }}">
                <input type="hidden" name="to" value="{{ $to }}">
                <input type="hidden" name="head" value="{{ $selectedHeadId }}">
                <div class="col-md-3">
                    <label class="form-label">Expense head</label>
                    <select name="expense_head_id" class="form-select" required>
                        <option value="">Select…</option>
                        @foreach($activeHeads as $head)
                            <option value="{{ $head->id }}" @selected(old('expense_head_id') == $head->id)>{{ $head->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Amount (৳)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="expense-amount" class="form-control" value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payee</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">None</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected(old('vendor_id') == $vendor->id)>
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        <a href="{{ route('admin.payees.index') }}">Manage payees</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Note</label>
                    <input type="text" name="note" class="form-control" value="{{ old('note') }}" required maxlength="255">
                </div>

                <div class="col-12">
                    <div class="border rounded-3 p-3 bg-light-subtle">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div>
                                <div class="fw-semibold">Media (optional)</div>
                                <div class="text-muted small">Connect uploaded gallery photos and/or paste external media URLs.</div>
                            </div>
                            <a href="{{ route('admin.attachments.index') }}" class="small" target="_blank" rel="noopener">Open Attachments</a>
                        </div>

                        @if($recentAttachments->isNotEmpty())
                            <div class="mb-3">
                                <div class="text-muted small mb-2">From gallery</div>
                                <div class="row g-2">
                                    @foreach($recentAttachments as $attachment)
                                        @php
                                            $checked = collect(old('attachment_ids', []))->contains($attachment->id);
                                        @endphp
                                        <div class="col-6 col-md-3 col-lg-2">
                                            <label class="border rounded-3 bg-white p-2 h-100 d-block position-relative"
                                                   style="cursor: pointer;">
                                                <input type="checkbox"
                                                       name="attachment_ids[]"
                                                       value="{{ $attachment->id }}"
                                                       class="form-check-input position-absolute top-0 end-0 m-2"
                                                       @checked($checked)>
                                                <div class="ratio ratio-1x1 mb-2 bg-light rounded overflow-hidden">
                                                    <img src="{{ $attachment->url() }}" alt="{{ $attachment->title }}"
                                                         style="object-fit: cover; width: 100%; height: 100%;">
                                                </div>
                                                <div class="small text-truncate" title="{{ $attachment->title }}">{{ $attachment->title }}</div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="alert alert-secondary small mb-3">
                                No gallery photos yet.
                                <a href="{{ route('admin.attachments.index') }}">Upload media</a> first, or paste URLs below.
                            </div>
                        @endif

                        <div>
                            <label class="form-label" for="media_urls">Media URLs</label>
                            <textarea name="media_urls" id="media_urls" rows="3" class="form-control"
                                      placeholder="https://example.com/bill.jpg&#10;One URL per line">{{ old('media_urls') }}</textarea>
                            <div class="form-text">Full http(s) links, one per line (or comma-separated).</div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="post_to_ledger" value="1" id="post_to_ledger" checked>
                        <label class="form-check-label" for="post_to_ledger">Also post cash-out to ledger</label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Save expense</button>
                </div>
            </form>
        </div>
            </x-slot:add>
        </x-mobile-panel-toggles>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-muted small">Showing filtered expenses</div>
            <div class="fw-semibold">Total: ৳{{ number_format((float) $total, 2) }}</div>
        </div>

        <div class="table-responsive bg-white border rounded-3 shadow-sm">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Head</th>
                        <th>Payee</th>
                        <th>Note</th>
                        <th>Media</th>
                        <th>Ledger</th>
                        <th class="text-end">Before</th>
                        <th class="text-end">After</th>
                        <th class="text-end">Amount</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        @php $mediaLinks = $expense->resolvedMedia($attachmentsById); @endphp
                        <tr>
                            <td>{{ $expense->entry_date?->format('d M Y') }}</td>
                            <td>{{ $expense->expenseHead?->label ?? '—' }}</td>
                            <td>{{ $expense->payeeName() ?: '—' }}</td>
                            <td>{{ $expense->note ?: '—' }}</td>
                            <td>
                                @if(count($mediaLinks))
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($mediaLinks as $i => $media)
                                            <a href="{{ $media['url'] }}" target="_blank" rel="noopener"
                                               class="btn btn-sm btn-outline-secondary"
                                               title="{{ $media['title'] }}">
                                                {{ $media['source'] === 'gallery' ? 'Photo' : 'URL' }}{{ count($mediaLinks) > 1 ? ' '.($i + 1) : '' }}
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($expense->ledgerEntry)
                                    <span class="badge text-bg-success">Posted</span>
                                @else
                                    <span class="badge text-bg-secondary">Not posted</span>
                                @endif
                            </td>
                            <td class="text-end">
                                {{ $expense->balance_before !== null ? number_format((float) $expense->balance_before, 2) : '—' }}
                            </td>
                            <td class="text-end">
                                {{ $expense->balance_after !== null ? number_format((float) $expense->balance_after, 2) : '—' }}
                            </td>
                            <td class="text-end">{{ number_format((float) $expense->amount, 2) }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.expenses.print', $expense) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Print</a>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="collapse" data-bs-target="#edit-{{ $expense->id }}">Edit</button>
                                <form method="post" action="{{ route('admin.expenses.destroy', $expense) }}" class="d-inline"
                                      onsubmit="return confirm('Remove this expense?')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="from" value="{{ $from }}">
                                    <input type="hidden" name="to" value="{{ $to }}">
                                    <input type="hidden" name="head" value="{{ $selectedHeadId }}">
                                    <button class="btn btn-sm btn-outline-danger">Del</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse" id="edit-{{ $expense->id }}">
                            <td colspan="10" class="bg-light">
                                @php
                                    $selectedAttachmentIds = $expense->mediaAttachmentIds();
                                    $existingMediaUrls = implode("\n", $expense->mediaUrls());
                                @endphp
                                <form method="post" action="{{ route('admin.expenses.update', $expense) }}" class="row g-2 p-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="from" value="{{ $from }}">
                                    <input type="hidden" name="to" value="{{ $to }}">
                                    <input type="hidden" name="head" value="{{ $selectedHeadId }}">
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">Expense head</label>
                                        <select name="expense_head_id" class="form-select form-select-sm" required>
                                            @foreach($heads as $head)
                                                <option value="{{ $head->id }}" @selected($expense->expense_head_id == $head->id)>
                                                    {{ $head->label }}@unless($head->is_active) (inactive)@endunless
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-1">Amount</label>
                                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm"
                                               value="{{ $expense->amount }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-1">Date</label>
                                        <input type="date" name="entry_date" class="form-control form-control-sm"
                                               value="{{ $expense->entry_date?->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small mb-1">Payee</label>
                                        <select name="vendor_id" class="form-select form-select-sm">
                                            <option value="">None</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}" @selected($expense->vendor_id == $vendor->id)>
                                                    {{ $vendor->name }}
                                                </option>
                                            @endforeach
                                            @if($expense->vendor_id && ! $vendors->contains('id', $expense->vendor_id) && $expense->vendor)
                                                <option value="{{ $expense->vendor->id }}" selected>
                                                    {{ $expense->vendor->name }} (inactive)
                                                </option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1">Note</label>
                                        <input type="text" name="note" class="form-control form-control-sm" value="{{ $expense->note }}" required>
                                    </div>

                                    <div class="col-12">
                                        <div class="border rounded-3 p-3 bg-white">
                                            <div class="fw-semibold small mb-2">Media (optional)</div>
                                            @if($recentAttachments->isNotEmpty())
                                                <div class="row g-2 mb-2">
                                                    @foreach($recentAttachments as $attachment)
                                                        <div class="col-6 col-md-3 col-lg-2">
                                                            <label class="border rounded-3 p-2 h-100 d-block position-relative"
                                                                   style="cursor: pointer;">
                                                                <input type="checkbox"
                                                                       name="attachment_ids[]"
                                                                       value="{{ $attachment->id }}"
                                                                       class="form-check-input position-absolute top-0 end-0 m-2"
                                                                       @checked(in_array($attachment->id, $selectedAttachmentIds, true))>
                                                                <div class="ratio ratio-1x1 mb-1 bg-light rounded overflow-hidden">
                                                                    <img src="{{ $attachment->url() }}" alt="{{ $attachment->title }}"
                                                                         style="object-fit: cover; width: 100%; height: 100%;">
                                                                </div>
                                                                <div class="small text-truncate" title="{{ $attachment->title }}">{{ $attachment->title }}</div>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <label class="form-label small" for="media_urls_{{ $expense->id }}">Media URLs</label>
                                            <textarea name="media_urls" id="media_urls_{{ $expense->id }}" rows="2"
                                                      class="form-control form-control-sm"
                                                      placeholder="https://example.com/bill.jpg">{{ $existingMediaUrls }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="post_to_ledger" value="1"
                                                   id="post_to_ledger_{{ $expense->id }}"
                                                   @checked($expense->ledgerEntry)>
                                            <label class="form-check-label" for="post_to_ledger_{{ $expense->id }}">
                                                Also post cash-out to ledger
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <button class="btn btn-sm btn-primary">Save</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No expenses in this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $expenses->links() }}</div>
    </div>
</div>
<script>
(function () {
    const preview = document.getElementById('expense-balance-preview');
    const amountInput = document.getElementById('expense-amount');
    const postCheckbox = document.getElementById('post_to_ledger');
    const afterEl = document.getElementById('balance-after');
    if (!preview || !amountInput || !postCheckbox || !afterEl) return;

    const before = parseFloat(preview.dataset.balance || '0');

    function formatMoney(value) {
        return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function updateAfter() {
        const amount = parseFloat(amountInput.value || '0');
        const hitLedger = postCheckbox.checked && !Number.isNaN(amount) && amount > 0;
        const after = hitLedger ? before - amount : before;
        afterEl.textContent = formatMoney(after);
        afterEl.classList.toggle('text-danger', after < 0);
    }

    amountInput.addEventListener('input', updateAfter);
    postCheckbox.addEventListener('change', updateAfter);
    updateAfter();
})();
</script>
@endsection
