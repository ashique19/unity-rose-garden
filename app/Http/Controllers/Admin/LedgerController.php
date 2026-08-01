<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountLedgerEntry;
use App\Models\Attachment;
use App\Models\ExpenseHead;
use App\Models\Flat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public function index(Request $request): View
    {
        $entries = AccountLedgerEntry::query()
            ->with(['flat', 'expenseHead'])
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $attachmentIds = $entries->getCollection()
            ->flatMap(fn (AccountLedgerEntry $entry) => $entry->mediaAttachmentIds())
            ->unique()
            ->values();

        $attachmentsById = $attachmentIds->isEmpty()
            ? collect()
            : Attachment::query()->whereIn('id', $attachmentIds)->get()->keyBy('id');

        $recentAttachments = Attachment::query()
            ->orderByDesc('created_at')
            ->limit(24)
            ->get();

        $flats = Flat::query()->orderBy('name')->get();

        return view('admin.ledger.index', [
            'entries' => $entries,
            'flats' => $flats,
            'expenseHeads' => ExpenseHead::query()->active()->ordered()->get(),
            'filterType' => $request->query('type'),
            'recentAttachments' => $recentAttachments,
            'attachmentsById' => $attachmentsById,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in([AccountLedgerEntry::TYPE_CASH_IN, AccountLedgerEntry::TYPE_CASH_OUT])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['required', 'date'],
            'flat_id' => ['nullable', 'integer', 'exists:flats,id'],
            'expense_head_id' => ['nullable', 'integer', 'exists:expense_heads,id'],
            'payee' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'attachment_ids' => ['nullable', 'array'],
            'attachment_ids.*' => ['integer', 'exists:attachments,id'],
            'media_urls' => ['nullable', 'string', 'max:4000'],
        ]);

        $head = null;
        if ($data['type'] === AccountLedgerEntry::TYPE_CASH_OUT) {
            if (empty($data['expense_head_id'])) {
                return back()->withErrors([
                    'expense_head_id' => 'Select an expense head for cash out.',
                ])->withInput();
            }
            if (empty($data['note'])) {
                return back()->withErrors([
                    'note' => 'A note is required for cash out / expenses.',
                ])->withInput();
            }
            $head = ExpenseHead::query()->findOrFail($data['expense_head_id']);
            $data['category'] = $head->label;
        }

        $media = $this->buildMediaPayload(
            $data['attachment_ids'] ?? [],
            $data['media_urls'] ?? null
        );

        if ($media === null) {
            return back()->withErrors([
                'media_urls' => 'One or more media URLs are invalid. Use full http(s) links, one per line.',
            ])->withInput();
        }

        AccountLedgerEntry::query()->create([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'entry_date' => $data['entry_date'],
            'flat_id' => $data['flat_id'] ?? null,
            'collection_id' => null,
            'expense_head_id' => $head?->id,
            'payee' => $data['payee'] ?? null,
            'category' => $data['category'] ?? null,
            'note' => $data['note'] ?? null,
            'media' => $media ?: null,
        ]);

        \App\Support\Auditor::log('ledger.'.$data['type'], null, [
            'amount' => $data['amount'],
            'flat_id' => $data['flat_id'] ?? null,
            'expense_head_id' => $head?->id,
            'category' => $data['category'] ?? null,
            'media_count' => count($media),
        ]);

        return redirect()
            ->route('admin.ledger.index')
            ->with('success', 'Ledger entry saved.');
    }

    public function destroy(AccountLedgerEntry $accountLedgerEntry): RedirectResponse
    {
        if ($accountLedgerEntry->collection_id) {
            return back()->withErrors([
                'ledger' => 'This entry is linked to a collection. Remove the collection instead.',
            ]);
        }

        $accountLedgerEntry->delete();

        return redirect()
            ->route('admin.ledger.index')
            ->with('success', 'Ledger entry removed.');
    }

    /**
     * @param  list<int|string>  $attachmentIds
     * @return list<array{attachment_id?: int, url?: string}>|null  null when URL validation fails
     */
    private function buildMediaPayload(array $attachmentIds, ?string $mediaUrlsText): ?array
    {
        $media = [];

        foreach ($attachmentIds as $id) {
            $media[] = ['attachment_id' => (int) $id];
        }

        $raw = trim((string) $mediaUrlsText);
        if ($raw !== '') {
            $parts = preg_split('/[\r\n,]+/', $raw) ?: [];
            foreach ($parts as $part) {
                $url = trim($part);
                if ($url === '') {
                    continue;
                }
                if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url)) {
                    return null;
                }
                $media[] = ['url' => $url];
            }
        }

        return $media;
    }
}
