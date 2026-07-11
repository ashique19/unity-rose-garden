<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountLedgerEntry;
use App\Models\Flat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public const CASH_OUT_CATEGORIES = [
        'maintenance',
        'salary',
        'utility',
        'supplies',
        'misc',
    ];

    public function index(Request $request): View
    {
        $entries = AccountLedgerEntry::query()
            ->with('flat')
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $flats = Flat::query()->orderBy('name')->get();

        return view('admin.ledger.index', [
            'entries' => $entries,
            'flats' => $flats,
            'categories' => self::CASH_OUT_CATEGORIES,
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
            'category' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['type'] === AccountLedgerEntry::TYPE_CASH_OUT && empty($data['category'])) {
            $data['category'] = 'misc';
        }

        AccountLedgerEntry::query()->create([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'entry_date' => $data['entry_date'],
            'flat_id' => $data['flat_id'] ?? null,
            'collection_id' => null,
            'category' => $data['category'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        \App\Support\Auditor::log('ledger.'.$data['type'], null, [
            'amount' => $data['amount'],
            'flat_id' => $data['flat_id'] ?? null,
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
