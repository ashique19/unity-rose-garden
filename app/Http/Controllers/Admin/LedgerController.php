<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountLedgerEntry;
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

        $flats = Flat::query()->orderBy('name')->get();

        return view('admin.ledger.index', [
            'entries' => $entries,
            'flats' => $flats,
            'expenseHeads' => ExpenseHead::query()->active()->ordered()->get(),
            'filterType' => $request->query('type'),
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
        ]);

        \App\Support\Auditor::log('ledger.'.$data['type'], null, [
            'amount' => $data['amount'],
            'flat_id' => $data['flat_id'] ?? null,
            'expense_head_id' => $head?->id,
            'category' => $data['category'] ?? null,
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
}
