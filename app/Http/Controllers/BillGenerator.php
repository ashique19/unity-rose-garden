<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillDetail;
use App\Models\MeterReading;
use App\Models\Flat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class BillGenerator extends Controller
{
    /**
     * Generate an LPG bill, converting volume (m3) to weight (kg) for Dhaka standards.
     */
    public function store(Request $request)
    {
        // 1. Validate the incoming form payload
        $request->validate([
            'month'      => 'required|string',        // Expects "2026-May" or "2026-05"
            'total_bill' => 'required|numeric|min:0', // Total bill value for KG distribution
        ]);

        // Define the standard LPG conversion factor: 1 m3 = 2.04 kg
        $m3ToKgMultiplier = 2.04;

        try {
            // Parse the chosen billing month
            $billingMonth  = \Carbon\Carbon::parse($request->input('month'))->startOfMonth();
            $previousMonth = $billingMonth->copy()->subMonth();

            // 2. Fetch current month's meter readings
            $currentReadings = MeterReading::whereYear('reading_date', $billingMonth->year)
                ->whereMonth('reading_date', $billingMonth->month)
                ->get()
                ->keyBy('flat_id');

            if ($currentReadings->isEmpty()) {
                return redirect()->back()->withErrors([
                    'error' => 'No meter readings found for ' . $billingMonth->format('F Y') . '. Please log readings first.'
                ]);
            }

            // OPTIMIZATION: Fetch all previous month's readings at once to avoid queries inside the loop
            $previousReadings = MeterReading::whereYear('reading_date', $previousMonth->year)
                ->whereMonth('reading_date', $previousMonth->month)
                ->get()
                ->keyBy('flat_id');

            // 3. Begin a Database Transaction
            DB::beginTransaction();

            // 4. Clean up any existing bill records for this exact month to allow regenerations
            Bill::where('bill_for_month', $billingMonth->toDateString())->delete();

            // 5. Loop through all flats to calculate usage and convert to KG
            $totalUsedM3      = 0;
            $totalUsedKg      = 0;
            $flatCalculations = [];
            $allFlats         = Flat::all();

            foreach ($allFlats as $flat) {
                // Get current month's reading unit
                $current     = $currentReadings->get($flat->id);
                $currentUnit = $current ? $current->reading_unit : 0;

                // Get previous month's reading unit from our pre-fetched collection
                $previous     = $previousReadings->get($flat->id);
                $previousUnit = $previous ? $previous->reading_unit : 0;

                // Calculate consumption in m3
                $usedM3 = max(0, $currentUnit - $previousUnit);
                
                // Convert gaseous volume (m3) to mass weight weight (KG) using multiplication
                $usedKg = $usedM3 * $m3ToKgMultiplier;

                // Accumulate building-wide totals
                $totalUsedM3 += $usedM3;
                $totalUsedKg += $usedKg;

                $flatCalculations[] = [
                    'flat_id'          => $flat->id,
                    'previous_reading' => $previousUnit,
                    'current_reading'  => $currentUnit,
                    'used_m3'          => $usedM3,
                    'used_kg'          => $usedKg,
                ];
            }

            // 6. Calculate the dynamic rates per kg and per m3 based on total bill money input
            $inputTotalBill = $request->input('total_bill');
            $pricePerKg     = $totalUsedKg > 0 ? ($inputTotalBill / $totalUsedKg) : 0;
            $pricePerM3     = $totalUsedM3 > 0 ? ($inputTotalBill / $totalUsedM3) : 0;

            // 7. Store the parent Bill tracking record with all totals populated
            $bill = Bill::create([
                'name'           => 'LPG Gas Bill - ' . $billingMonth->format('F Y'),
                'bill_for_month' => $billingMonth->toDateString(),
                'price_per_kg'   => $pricePerKg, // Dynamic price per KG
                'price_per_m3'   => $pricePerM3, // Maintained for database compatibility
                'total_used_m3'  => $totalUsedM3,
                'total_used_kg'  => $totalUsedKg,
                'total_bill'     => $inputTotalBill,
            ]);

            // 8. Store detailed individual logs for each flat
            foreach ($flatCalculations as $calc) {
                BillDetail::create([
                    'bill_id'          => $bill->id,
                    'flat_id'          => $calc['flat_id'],
                    'previous_reading' => $calc['previous_reading'],
                    'current_reading'  => $calc['current_reading'],
                    'used_m3'          => $calc['used_m3'],
                    'used_kg'          => $calc['used_kg'], // Converted mass weight
                    'bill_for_month'   => $billingMonth->toDateString(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'LPG Bill generated successfully for ' . $billingMonth->format('F Y'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors([
                'error' => 'Billing Generation Failed: ' . $e->getMessage()
            ]);
        }
    }



    public function index()
    {
        $dropdownOptions = [];
        
        // Loop to calculate the consumption data for the past 6 months
        for ($i = -1; $i > -6; $i--) {
            $currentMonth = \Carbon\Carbon::now()->addMonths($i);
            $prevMonth = (clone $currentMonth)->subMonth();

            // Sum of all readings for the current loop month
            $currentSum = \App\Models\MeterReading::whereYear('reading_date', $currentMonth->year)
                ->whereMonth('reading_date', $currentMonth->month)
                ->sum('reading_unit');

            // Sum of all readings for the previous month
            $prevSum = \App\Models\MeterReading::whereYear('reading_date', $prevMonth->year)
                ->whereMonth('reading_date', $prevMonth->month)
                ->sum('reading_unit');

            // Total consumption value for this month (Current Month - Previous Month)
            $monthlyValue = $currentSum - $prevSum;

            // Total volume consumed in m3
            $volumeM3 = $currentSum - $prevSum;

            // Convert the volume figure to mass (kg) by dividing by 2.04
            $massKg = $volumeM3 * 2.04;

            // Format the string label to display in the dropdown option
            $dropdownOptions[] = [
                'value' => $currentMonth->format('Y-M'),
                'label' => $currentMonth->format('Y-M') . " (" . number_format($volumeM3, 2) . " m³)". " (" . number_format($massKg, 2) . " kg)"
            ];
        }

        // Pass the calculated options array down to your generator blade view
        return view('bills.generator', compact('dropdownOptions'));
    }

    /**
     * Display a listing of generated bills.
     */
    public function history()
    {
        // Fetch bills from newest to oldest
        $bills = Bill::orderBy('bill_for_month', 'desc')->get();

        return view('bills.history', compact('bills'));
    }


    /**
     * Display individual flat breakdowns for a specific billing month.
     */
    public function show($dateString)
    {
        try {
            // 1. Carbon safely parses strings like "2026-May" or "2026-05" 
            // into a complete date object: 2026-05-01 00:00:00
            $date = \Illuminate\Support\Carbon::parse($dateString)->startOfMonth();

            // 2. Query using ->toDateString() which matches the MySQL standard date format (YYYY-MM-DD)
            $bill = Bill::whereDate('bill_for_month', $date->toDateString())
                        ->with(['details.flat'])
                        ->firstOrFail();

            return view('bills.show', compact('bill'));
            
        } catch (\Exception $e) {
            // Redirects back to your history view with a clear message
            return redirect()->route('bill-history')->withErrors([
                'error' => 'Could not find any generated bill records for ' . htmlspecialchars($dateString)
            ]);
        }
    }


}
