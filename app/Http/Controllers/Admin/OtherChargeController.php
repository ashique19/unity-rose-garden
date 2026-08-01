<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillType;
use App\Models\CustomCharge;
use App\Models\Flat;
use App\Support\BillMonth;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OtherChargeController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->resolveMonth($request->query('month'));
        $monthKey = $month->toDateString();

        $flats = Flat::query()->orderBy('name')->get();
        $billTypes = BillType::query()
            ->ordered()
            ->otherCharges()
            ->get();

        $charges = CustomCharge::query()
            ->with(['flat', 'billType'])
            ->whereDate('charge_month', $monthKey)
            ->orderBy('flat_id')
            ->get();

        return view('admin.other-charges.index', [
            'selectedMonth' => $month,
            'flats' => $flats,
            'billTypes' => $billTypes,
            'charges' => $charges,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $month = $this->resolveMonth($request->input('charge_month'));

        $data = $request->validate([
            'flat_id' => ['required', 'integer', 'exists:flats,id'],
            'bill_type_id' => ['required', 'integer', 'exists:bill_types,id'],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $flat = Flat::query()->findOrFail($data['flat_id']);
        $billType = BillType::query()->findOrFail($data['bill_type_id']);

        if ($billType->isMeterFlat() || $billType->isCommonMeter()) {
            return back()->withErrors([
                'bill_type_id' => 'Use gas readings or Water bills entry for this type.',
            ])->withInput();
        }

        if (! $flat->isBillTypeEnabled($billType->key)) {
            return back()->withErrors([
                'bill_type_id' => $billType->label.' is disabled for flat '.$flat->name.'.',
            ])->withInput();
        }

        CustomCharge::query()->create([
            'flat_id' => $flat->id,
            'bill_type_id' => $billType->id,
            'charge_month' => $month->toDateString(),
            'label' => $data['label'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('admin.other-charges.index', ['month' => $month->format('Y-m')])
            ->with('success', 'Charge added for '.$flat->name.'.');
    }

    public function destroy(CustomCharge $customCharge): RedirectResponse
    {
        $month = $customCharge->charge_month->format('Y-m');
        $customCharge->delete();

        return redirect()
            ->route('admin.other-charges.index', ['month' => $month])
            ->with('success', 'Charge removed.');
    }

    private function resolveMonth(?string $month): Carbon
    {
        return BillMonth::parse($month);
    }
}
