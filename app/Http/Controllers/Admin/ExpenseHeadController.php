<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseHead;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExpenseHeadController extends Controller
{
    public function index(): View
    {
        $heads = ExpenseHead::query()
            ->withCount(['expenses', 'ledgerEntries'])
            ->ordered()
            ->get();

        return view('admin.expense-heads.index', [
            'heads' => $heads,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'key' => ['nullable', 'string', 'max:80', 'alpha_dash', 'unique:expense_heads,key'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $key = ! empty($data['key']) ? $data['key'] : Str::slug($data['label'], '_');
        if ($key === '') {
            $key = 'head_'.Str::lower(Str::random(6));
        }

        if (ExpenseHead::query()->where('key', $key)->exists()) {
            $key = $key.'_'.Str::lower(Str::random(4));
        }

        $head = ExpenseHead::query()->create([
            'key' => $key,
            'label' => $data['label'],
            'sort_order' => $data['sort_order'] ?? ((int) ExpenseHead::query()->max('sort_order') + 10),
            'is_active' => true,
        ]);

        Auditor::log('expense_head.created', $head, ['label' => $head->label]);

        return redirect()
            ->route('admin.expense-heads.index')
            ->with('success', 'Expense head “'.$head->label.'” created.');
    }

    public function update(Request $request, ExpenseHead $expenseHead): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $expenseHead->update([
            'label' => $data['label'],
            'sort_order' => $data['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        Auditor::log('expense_head.updated', $expenseHead, [
            'label' => $expenseHead->label,
            'is_active' => $expenseHead->is_active,
        ]);

        return redirect()
            ->route('admin.expense-heads.index')
            ->with('success', 'Expense head “'.$expenseHead->label.'” updated.');
    }

    public function destroy(ExpenseHead $expenseHead): RedirectResponse
    {
        if ($expenseHead->isInUse()) {
            return back()->withErrors([
                'head' => 'Cannot delete “'.$expenseHead->label.'” because expenses use it. Deactivate it instead.',
            ]);
        }

        $label = $expenseHead->label;
        Auditor::log('expense_head.deleted', $expenseHead, ['label' => $label]);
        $expenseHead->delete();

        return redirect()
            ->route('admin.expense-heads.index')
            ->with('success', 'Expense head “'.$label.'” removed.');
    }
}
