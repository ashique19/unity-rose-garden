<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountLedgerEntry;
use App\Models\Building;
use App\Models\Expense;
use App\Models\ExpenseHead;
use App\Support\Auditor;
use App\Support\BillMonth;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);
        $headId = $request->query('head');

        $query = Expense::query()
            ->with(['expenseHead', 'ledgerEntry'])
            ->when($from, fn ($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('entry_date', '<=', $to))
            ->when($headId, fn ($q) => $q->where('expense_head_id', $headId))
            ->orderByDesc('entry_date')
            ->orderByDesc('id');

        $expenses = (clone $query)->paginate(30)->withQueryString();
        $total = (clone $query)->sum('amount');
        $building = Building::query()->first();
        $balance = $building ? $building->balanceAmount() : 0.0;

        return view('admin.expenses.index', [
            'expenses' => $expenses,
            'total' => $total,
            'heads' => ExpenseHead::query()->ordered()->get(),
            'activeHeads' => ExpenseHead::query()->active()->ordered()->get(),
            'from' => $from,
            'to' => $to,
            'selectedHeadId' => $headId,
            'availableBalance' => $balance,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedExpense($request);
        $head = ExpenseHead::query()->findOrFail($data['expense_head_id']);

        DB::transaction(function () use ($data, $head, $request) {
            $building = Building::query()->firstOrFail();
            $balanceBefore = $building->balanceAmount();
            $postToLedger = $request->boolean('post_to_ledger');
            $amount = (float) $data['amount'];
            $balanceAfter = $postToLedger ? $balanceBefore - $amount : $balanceBefore;

            $expense = Expense::query()->create([
                'expense_head_id' => $head->id,
                'amount' => $data['amount'],
                'entry_date' => $data['entry_date'],
                'payee' => $data['payee'] ?? null,
                'note' => $data['note'],
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            if ($postToLedger) {
                $this->createLedgerEntry($expense, $head);
            }

            Auditor::log('expense.created', $expense, [
                'head' => $head->label,
                'amount' => $data['amount'],
                'posted_to_ledger' => $postToLedger,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);
        });

        return redirect()
            ->route('admin.expenses.index', $this->filterQuery($request))
            ->with('success', 'Expense recorded.');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $this->validatedExpense($request);
        $head = ExpenseHead::query()->findOrFail($data['expense_head_id']);

        DB::transaction(function () use ($data, $head, $request, $expense) {
            $building = Building::query()->firstOrFail();
            $postToLedger = $request->boolean('post_to_ledger');
            $ledger = $expense->ledgerEntry;
            $current = $building->balanceAmount();
            // Undo this expense's existing cash-out so the snapshot reflects this edit.
            $balanceBefore = $ledger ? $current + (float) $ledger->amount : $current;
            $amount = (float) $data['amount'];
            $balanceAfter = $postToLedger ? $balanceBefore - $amount : $balanceBefore;

            $expense->update([
                'expense_head_id' => $head->id,
                'amount' => $data['amount'],
                'entry_date' => $data['entry_date'],
                'payee' => $data['payee'] ?? null,
                'note' => $data['note'],
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            if ($postToLedger) {
                if ($ledger) {
                    $ledger->update([
                        'amount' => $data['amount'],
                        'entry_date' => $data['entry_date'],
                        'expense_head_id' => $head->id,
                        'category' => $head->label,
                        'payee' => $data['payee'] ?? null,
                        'note' => $data['note'],
                    ]);
                } else {
                    $this->createLedgerEntry($expense->fresh(), $head);
                }
            } elseif ($ledger) {
                $ledger->delete();
            }

            Auditor::log('expense.updated', $expense, [
                'head' => $head->label,
                'amount' => $data['amount'],
                'posted_to_ledger' => $postToLedger,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);
        });

        return redirect()
            ->route('admin.expenses.index', $this->filterQuery($request))
            ->with('success', 'Expense updated.');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        DB::transaction(function () use ($expense) {
            Auditor::log('expense.deleted', $expense, [
                'amount' => $expense->amount,
            ]);

            AccountLedgerEntry::query()
                ->where('expense_id', $expense->id)
                ->delete();

            $expense->delete();
        });

        return redirect()
            ->route('admin.expenses.index', $this->filterQuery($request))
            ->with('success', 'Expense removed.');
    }

    public function printList(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);
        $headId = $request->query('head');

        $expenses = Expense::query()
            ->with('expenseHead')
            ->when($from, fn ($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('entry_date', '<=', $to))
            ->when($headId, fn ($q) => $q->where('expense_head_id', $headId))
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $head = $headId ? ExpenseHead::query()->find($headId) : null;

        return view('admin.expenses.print-list', [
            'building' => Building::query()->first(),
            'expenses' => $expenses,
            'total' => $expenses->sum(fn ($e) => (float) $e->amount),
            'from' => $from,
            'to' => $to,
            'head' => $head,
            'printedBy' => $request->user()?->name,
        ]);
    }

    public function printOne(Request $request, Expense $expense): View
    {
        $expense->load('expenseHead');

        return view('admin.expenses.print', [
            'building' => Building::query()->first(),
            'expense' => $expense,
            'printedBy' => $request->user()?->name,
        ]);
    }

    private function createLedgerEntry(Expense $expense, ExpenseHead $head): AccountLedgerEntry
    {
        return AccountLedgerEntry::query()->create([
            'type' => AccountLedgerEntry::TYPE_CASH_OUT,
            'amount' => $expense->amount,
            'entry_date' => $expense->entry_date,
            'flat_id' => null,
            'collection_id' => null,
            'expense_id' => $expense->id,
            'expense_head_id' => $head->id,
            'category' => $head->label,
            'payee' => $expense->payee,
            'note' => $expense->note,
        ]);
    }

    private function validatedExpense(Request $request): array
    {
        return $request->validate([
            'expense_head_id' => ['required', 'integer', 'exists:expense_heads,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['required', 'date'],
            'payee' => ['nullable', 'string', 'max:120'],
            'note' => ['required', 'string', 'max:255'],
            'post_to_ledger' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveRange(Request $request): array
    {
        $from = $request->query('from');
        $to = $request->query('to');

        if ($month = $request->query('month')) {
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $start = BillMonth::parse($month);
                $from = $from ?: $start->toDateString();
                $to = $to ?: $start->copy()->endOfMonth()->toDateString();
            }
        }

        if (! $from && ! $to && ! $request->query('month') && ! $request->query('head')) {
            $start = now()->startOfMonth();
            $from = $start->toDateString();
            $to = $start->copy()->endOfMonth()->toDateString();
        }

        return [$from, $to];
    }

    private function filterQuery(Request $request): array
    {
        return array_filter([
            'from' => $request->input('from', $request->query('from')),
            'to' => $request->input('to', $request->query('to')),
            'month' => $request->input('month', $request->query('month')),
            'head' => $request->input('head', $request->query('head')),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
