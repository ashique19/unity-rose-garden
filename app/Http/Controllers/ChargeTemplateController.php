<?php

namespace App\Http\Controllers;

use App\Models\BillType;
use App\Models\ChargeTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChargeTemplateController extends Controller
{
    public function index()
    {
        $templates = ChargeTemplate::query()->with('billType')->orderBy('created_at', 'desc')->get();
        $billTypes = BillType::query()->ordered()->whereNotIn('key', ['gas', 'water'])->get();

        return view('charge_templates.index', compact('templates', 'billTypes'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedTemplate($request);

        ChargeTemplate::create($validated);

        return redirect()->route('charge-templates.index')->with('success', 'Charge template created successfully!');
    }

    public function update(Request $request, ChargeTemplate $chargeTemplate)
    {
        $validated = $this->validatedTemplate($request, $chargeTemplate);

        $chargeTemplate->update($validated);

        return redirect()->route('charge-templates.index')->with('success', 'Charge template updated successfully!');
    }

    public function destroy(ChargeTemplate $chargeTemplate)
    {
        $chargeTemplate->delete();

        return redirect()->route('charge-templates.index')->with('success', 'Charge template deleted successfully!');
    }

    /**
     * @return array{bill_type_id: ?int, charge_key: string, label: string, default_amount: mixed, is_building_wide: bool}
     */
    private function validatedTemplate(Request $request, ?ChargeTemplate $chargeTemplate = null): array
    {
        $buildingWide = $request->boolean('is_building_wide');

        $validated = $request->validate([
            'bill_type_id' => [
                Rule::requiredIf($buildingWide),
                'nullable',
                'integer',
                'exists:bill_types,id',
            ],
            'charge_key' => [
                'required',
                'alpha_dash',
                Rule::unique('charge_templates', 'charge_key')->ignore($chargeTemplate?->id),
            ],
            'label' => 'required|string|max:255',
            'default_amount' => 'required|numeric|min:0',
            'is_building_wide' => 'sometimes|boolean',
        ]);

        $validated['is_building_wide'] = $buildingWide;
        $validated['bill_type_id'] = $validated['bill_type_id'] ?? null;

        return $validated;
    }
}
