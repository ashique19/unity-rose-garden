@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold text-dark mb-1">Cashbook ledger</h1>
                <p class="text-muted mb-0">Cash in (flat optional) and cash out. Optionally link bill photos or media URLs.</p>
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
                        <option value="cash_in" @selected(old('type') === 'cash_in')>Cash in</option>
                        <option value="cash_out" @selected(old('type') === 'cash_out')>Cash out</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Amount (৳)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required value="{{ old('amount') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Flat (optional)</label>
                    <select name="flat_id" class="form-select">
                        <option value="">None</option>
                        @foreach($flats as $flat)
                            <option value="{{ $flat->id }}" @selected(old('flat_id') == $flat->id)>{{ $flat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Expense head</label>
                    <select name="expense_head_id" class="form-select">
                        <option value="">— (cash in)</option>
                        @foreach($expenseHeads as $head)
                            <option value="{{ $head->id }}" @selected(old('expense_head_id') == $head->id)>{{ $head->label }}</option>
                        @endforeach
                    </select>
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
                <div class="col-md-4">
                    <label class="form-label">Note</label>
                    <input type="text" name="note" class="form-control" value="{{ old('note') }}">
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
                        <th>Category / head</th>
                        <th>Payee</th>
                        <th>Note</th>
                        <th>Media</th>
                        <th class="text-end">Amount</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        @php $mediaLinks = $entry->resolvedMedia($attachmentsById); @endphp
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
                            <td>{{ $entry->expenseHead?->label ?? ($entry->category ? ucfirst($entry->category) : '—') }}</td>
                            <td>{{ $entry->vendor?->name ?? ($entry->payee ?: '—') }}</td>
                            <td class="text-muted">{{ $entry->note ?: '—' }}</td>
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
                            <td colspan="9" class="text-center text-muted py-4">No ledger entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $entries->links() }}</div>
    </div>
</div>
@endsection
