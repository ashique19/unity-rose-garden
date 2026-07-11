<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flat;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FlatController extends Controller
{
    public function index(): View
    {
        $flats = Flat::query()
            ->orderBy('name')
            ->get()
            ->sortBy(function (Flat $flat) {
                preg_match('/^(\d+)([A-Z])$/i', $flat->name, $m);

                return [isset($m[1]) ? (int) $m[1] : 0, $m[2] ?? $flat->name];
            })
            ->values();

        return view('admin.flats.index', [
            'flats' => $flats,
        ]);
    }

    public function update(Request $request, Flat $flat): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('flats', 'name')->ignore($flat->id),
            ],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'digits:11'],
            'status' => ['required', Rule::in(['online', 'offline'])],
        ]);

        $flat->update($data);

        Auditor::log('flat.updated', $flat, [
            'name' => $flat->name,
            'contact_name' => $flat->contact_name,
            'phone' => $flat->phone,
            'status' => $flat->status,
        ]);

        return redirect()
            ->route('admin.flats.index')
            ->with('success', 'Flat '.$flat->name.' updated.');
    }
}
