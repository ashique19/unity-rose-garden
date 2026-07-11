<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillType;
use App\Models\Flat;
use App\Models\FlatBillTypeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FlatBillTypeSettingController extends Controller
{
    public function index(): View
    {
        $flats = $this->sortedFlats(
            Flat::query()->with(['billTypeSettings.billType'])->get()
        );

        $billTypes = BillType::query()->ordered()->get();

        return view('admin.flats.bill-type-settings', compact('flats', 'billTypes'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'array'],
        ]);

        $enabledMap = $validated['enabled'] ?? [];

        $flats = Flat::query()->get();
        $billTypes = BillType::query()->get();

        foreach ($flats as $flat) {
            foreach ($billTypes as $billType) {
                $key = $flat->id.'_'.$billType->id;
                FlatBillTypeSetting::query()->updateOrCreate(
                    [
                        'flat_id' => $flat->id,
                        'bill_type_id' => $billType->id,
                    ],
                    [
                        'enabled' => isset($enabledMap[$key]),
                    ]
                );
            }
        }

        return back()->with('success', 'Bill type participation updated.');
    }

    private function sortedFlats(Collection $flats): Collection
    {
        return $flats->sortBy(function (Flat $flat) {
            preg_match('/^(\d+)([A-Z])$/i', $flat->name, $m);

            return [isset($m[1]) ? (int) $m[1] : 0, $m[2] ?? $flat->name];
        })->values();
    }
}
