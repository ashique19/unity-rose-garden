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
        // Start your query builder and select meter_readings columns
        $query = MeterReading::query()
            ->select('meter_readings.*')
            ->join('flats', 'meter_readings.flat_id', '=', 'flats.id')
            ->with('flat')
            // Sort naturally: by length first (so 2-char '2A' comes before 3-char '10A'), then alphabetically
            ->orderByRaw('LENGTH(flats.name) ASC')
            ->orderBy('flats.name', 'ASC');

        // Check if the query parameter 'q' exists
        if ($request->has('q') && !empty($request->query('q'))) {
            try {
                $date = \Carbon\Carbon::parse($request->query('q'));

                $query->whereYear('meter_readings.reading_date', $date->year)
                    ->whereMonth('meter_readings.reading_date', $date->month);
            } catch (\Exception $e) {
                // Handle invalid date format gracefully
            }
        }

        // Fetch exactly 18 flats per page
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