<?php

namespace App\Http\Controllers;

use App\Models\MeterReading;
use Illuminate\Http\Request;
use \App\Models\Flat;
use Exception;
use Carbon\Carbon;
// Store a newly created reading in storage
use Illuminate\Support\Facades\DB;
use App\Models\CustomCharge;


class MeterReadingAndChargesController extends Controller
{
    public function index(Request $request)
    {
        // Determine active query month context safely
        $activeMonth = now(); 
        if ($request->has('q') && !empty($request->query('q'))) {
            try {
                $activeMonth = \Carbon\Carbon::parse($request->query('q'));
            } catch (\Exception $e) {}
        }

        $activeYearStr = $activeMonth->year;
        $activeMonthStr = $activeMonth->month;

        // Fetch readings and scope custom charges strictly to the visible month context
        $readings = MeterReading::query()
            ->select('meter_readings.*')
            ->join('flats', 'meter_readings.flat_id', '=', 'flats.id')
            ->with(['flat', 'flat.customCharges' => function ($chargeQuery) use ($activeYearStr, $activeMonthStr) {
                // This prevents old month charges from leaking into the current view
                $chargeQuery->whereYear('charge_month', $activeYearStr)
                            ->whereMonth('charge_month', $activeMonthStr);
            }])
            ->orderByRaw('LENGTH(flats.name) ASC')
            ->orderBy('flats.name', 'ASC')
            ->when($request->has('q') && !empty($request->query('q')), function($q) use ($activeYearStr, $activeMonthStr) {
                return $q->whereYear('meter_readings.reading_date', $activeYearStr)
                        ->whereMonth('meter_readings.reading_date', $activeMonthStr);
            })
            ->paginate(18);
        
        return view('meter_readings_charges.index', compact('readings', 'request', 'activeMonth'));
    }

    // Show the form for creating a new reading
    public function create()
    {
        // 1. Fetch all flats to populate your core identity dropdown
        $flats = Flat::all();

        // 2. Fetch all predefined charge templates to feed our dynamic Alpine.js array data
        $chargeTemplates = \App\Models\ChargeTemplate::all();

        // 3. Compact and forward both variables cleanly to your unified interface layout
        return view('meter_readings_charges.create', compact('flats', 'chargeTemplates'));
    }


    public function store(Request $request)
    {

        // 1. Validate both core meter entries and the dynamic custom charges array
        $validated = $request->validate([
            'flat_id'                  => 'required|exists:flats,id',
            'reading_date'             => 'required|date',
            'reading_unit'             => 'required|numeric|min:0',
            
            // Array validation rules for fluid charges
            'custom_charges'           => 'nullable|array',
            'custom_charges.*.label'   => 'required_with:custom_charges|string|max:255',
            'custom_charges.*.amount'  => 'required_with:custom_charges|numeric|min:0',
            'custom_charges.*.notes'   => 'nullable|string|max:500',
        ]);

        $flatId = $request->input('flat_id');
        $readingDate = \Carbon\Carbon::parse($request->input('reading_date'));
        $newReading = $request->input('reading_unit');

        // 2. Proactive Error Prevention: Check the previous logged reading
        $previousReading = MeterReading::where('flat_id', $flatId)
            ->where('reading_date', '<', $readingDate->toDateString())
            ->orderBy('reading_date', 'desc')
            ->first();

        // Block submission if a user inputs a lower reading value (typo protection)
        if ($previousReading && $newReading < $previousReading->reading_unit) {
            // Handle explicit string formatting safely even if reading_date is a string or Carbon instance
            $prevDateStr = $previousReading->reading_date instanceof \Carbon\Carbon 
                ? $previousReading->reading_date->format('Y-m-d') 
                : \Carbon\Carbon::parse($previousReading->reading_date)->format('Y-m-d');

            return redirect()->back()
                ->withErrors(['reading_unit' => "Typo Protection: The input reading ({$newReading}) cannot be lower than the previous reading ({$previousReading->reading_unit}) logged on {$prevDateStr}."])
                ->withInput();
        }

        // 3. Database Transaction Envelope to ensure absolute data consistency
        DB::beginTransaction();

        try {
            // Insert the core Gas Meter Reading
            MeterReading::create([
                'flat_id'      => $validated['flat_id'],
                'reading_date' => $validated['reading_date'],
                'reading_unit' => $validated['reading_unit'],
            ]);

            // 4. Process and save dynamic line charges if any were submitted
            // 4. Process and save dynamic line charges if any were submitted
            if ($request->has('custom_charges') && is_array($request->input('custom_charges'))) {
                
                // FORCE the date to freeze at the absolute first day of that target calendar month (e.g., '2026-07-01')
                $chargeMonth = $readingDate->copy()->startOfMonth()->toDateString();

                // CRITICAL CLEANUP: Delete any pre-existing custom charges for this flat in this EXACT month 
                // to prevent duplicate stacking if the manager re-submits or edits.
                CustomCharge::where('flat_id', $flatId)
                            ->where('charge_month', $chargeMonth)
                            ->delete();

                foreach ($request->input('custom_charges') as $chargeData) {
                    if (empty($chargeData['label'])) {
                        continue;
                    }

                    CustomCharge::create([
                        'flat_id'      => $flatId,
                        'charge_month' => $chargeMonth, // Saves as 'Y-m-01' matching the ledger framework exactly
                        'label'        => $chargeData['label'],
                        'amount'       => $chargeData['amount'],
                        'notes'        => $chargeData['notes'] ?? null,
                    ]);
                }
            }

            // Commit all records to the database safely
            DB::commit();

            return redirect()->route('meter-readings-and-charges.index')
                            ->with('success', 'Meter reading and monthly ledger items recorded successfully.');

        } catch (\Illuminate\Database\QueryException $e) {
            // Rollback all database actions if anything fails
            DB::rollBack();

            // Integrity constraint violation (SQLSTATE 23000) handles unique key crashes
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), '1062 Duplicate entry')) {
                return redirect()->back()
                    ->withErrors(['reading_date' => "Duplicate Entry: A record has already been locked for this flat on this target month timeline."])
                    ->withInput();
            }

            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Display the specified reading
    public function show(MeterReading $meterReading)
    {
        return view('meter_readings_charges.show', compact('meterReading'));
    }

    // Show the form for editing the specified reading
    public function edit(MeterReading $meterReading)
    {
        // return $meterReading;
        $flats = Flat::select('id', 'name')->get();
        return view('meter_readings_charges.edit', compact('meterReading', 'flats'));
    }

    // Update the specified reading in storage
    public function update(Request $request, MeterReading $meterReading)
    {
        $validated = $request->validate([
            'flat_id' => 'required|exists:flats,id',
            'reading_date' => 'required|date',
            'reading_unit' => 'required|numeric|min:0',
        ]);

        $meterReading->update($validated);

        return back()->with('success', 'Meter reading updated successfully.');
    }

    // Remove the specified reading from storage
    public function destroy(MeterReading $meterReading)
    {
        $meterReading->delete();

        return back()->with('success', 'Meter reading deleted successfully.');
    }
}