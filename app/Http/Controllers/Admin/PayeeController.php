<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayeeController extends Controller
{
    public function index(): View
    {
        $vendors = Vendor::query()
            ->withCount('ledgerEntries')
            ->ordered()
            ->get();

        return view('admin.payees.index', [
            'vendors' => $vendors,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:vendors,name'],
            'phone' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $vendor = Vendor::query()->create([
            'name' => trim($data['name']),
            'phone' => $data['phone'] ?? null,
            'note' => $data['note'] ?? null,
            'sort_order' => $data['sort_order'] ?? ((int) Vendor::query()->max('sort_order') + 10),
            'is_active' => true,
        ]);

        Auditor::log('vendor.created', $vendor, ['name' => $vendor->name]);

        return redirect()
            ->route('admin.payees.index')
            ->with('success', 'Payee “'.$vendor->name.'” registered.');
    }

    public function update(Request $request, Vendor $payee): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('vendors', 'name')->ignore($payee->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payee->update([
            'name' => trim($data['name']),
            'phone' => $data['phone'] ?? null,
            'note' => $data['note'] ?? null,
            'sort_order' => $data['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        Auditor::log('vendor.updated', $payee, [
            'name' => $payee->name,
            'is_active' => $payee->is_active,
        ]);

        return redirect()
            ->route('admin.payees.index')
            ->with('success', 'Payee “'.$payee->name.'” updated.');
    }

    public function destroy(Vendor $payee): RedirectResponse
    {
        if ($payee->isInUse()) {
            return back()->withErrors([
                'payee' => 'Cannot delete “'.$payee->name.'” because ledger entries use it. Deactivate it instead.',
            ]);
        }

        $name = $payee->name;
        Auditor::log('vendor.deleted', $payee, ['name' => $name]);
        $payee->delete();

        return redirect()
            ->route('admin.payees.index')
            ->with('success', 'Payee “'.$name.'” removed.');
    }
}
