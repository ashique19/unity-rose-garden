<?php

namespace App\Http\Controllers;

use App\Models\ChargeTemplate;
use Illuminate\Http\Request;

class ChargeTemplateController extends Controller
{
    public function index()
    {
        $templates = ChargeTemplate::orderBy('created_at', 'desc')->get();
        return view('charge_templates.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'charge_key'       => 'required|alpha_dash|unique:charge_templates,charge_key',
            'label'            => 'required|string|max:255',
            'default_amount'   => 'required|numeric|min:0',
            'is_building_wide' => 'boolean',
        ]);

        // Explicitly set boolean check for checkbox state
        $validated['is_building_wide'] = $request->has('is_building_wide');

        ChargeTemplate::create($validated);

        return redirect()->route('charge-templates.index')->with('success', 'Charge template created successfully!');
    }

    public function update(Request $request, ChargeTemplate $chargeTemplate)
    {
        $validated = $request->validate([
            'charge_key'       => 'required|alpha_dash|unique:charge_templates,charge_key,' . $chargeTemplate->id,
            'label'            => 'required|string|max:255',
            'default_amount'   => 'required|numeric|min:0',
            'is_building_wide' => 'boolean',
        ]);

        $validated['is_building_wide'] = $request->has('is_building_wide');

        $chargeTemplate->update($validated);

        return redirect()->route('charge-templates.index')->with('success', 'Charge template updated successfully!');
    }

    public function destroy(ChargeTemplate $chargeTemplate)
    {
        $chargeTemplate->delete();
        return redirect()->route('charge-templates.index')->with('success', 'Charge template deleted successfully!');
    }
}