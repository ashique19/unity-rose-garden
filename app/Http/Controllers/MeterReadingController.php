<?php

namespace App\Http\Controllers;

use App\Models\MeterReading;
use Illuminate\Http\Request;
use \App\Models\Flat;
use Exception;
use Carbon\Carbon;

class MeterReadingController extends Controller
{
    // Display a listing of the readings
    public function index(Request $request)
    {
        // Start your query builder instance
        $query = MeterReading::query()->with('flat'); // eager load the flat relationship

        // Check if the query parameter 'q' exists
        if ($request->has('q') && !empty($request->query('q'))) {
            try {
                // Carbon safely parses strings like "2026-May" or "2026-05"
                $date = Carbon::parse($request->query('q'));

                // Filter by year and month
                $query->whereYear('reading_date', $date->year)
                      ->whereMonth('reading_date', $date->month);
            } catch (Exception $e) {
                // Optional: handle invalid date format gracefully 
                // (e.g., ignore the filter or return a validation error)
            }
        }

        // Fetch the results (or use ->paginate(15) if you want pagination)
        $readings = $query->paginate(18);
        return view('meter_readings.index', compact('readings', 'request'));
    }

    // Show the form for creating a new reading
    public function create()
    {
        // You would typically pass flats to the view to populate a dropdown
        $flats = Flat::all();
        return view('meter_readings.create', compact('flats'));
    }

    // Store a newly created reading in storage
    public function store(Request $request)
    {
        $validated = $request->validate([
            'flat_id' => 'required|exists:flats,id',
            'reading_date' => 'required|date',
            'reading_unit' => 'required|numeric|min:0',
        ]);

        MeterReading::create($validated);

        return 1;

        return redirect()->route('meter-readings.index')
                         ->with('success', 'Meter reading recorded successfully.');
    }

    // Display the specified reading
    public function show(MeterReading $meterReading)
    {
        return view('meter_readings.show', compact('meterReading'));
    }

    // Show the form for editing the specified reading
    public function edit(MeterReading $meterReading)
    {
        // return $meterReading;
        $flats = Flat::select('id', 'name')->get();
        return view('meter_readings.edit', compact('meterReading', 'flats'));
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