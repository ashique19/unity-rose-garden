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
        $request->validate([
            'month'        => 'required|string',          
            'price_per_kg' => 'required|numeric|min:0',   
        ]);

        $m3ToKgMultiplier = env('M3_TO_KG_CONVERSION_RATE', 2.04);

        try {
            $billingMonth  = \Carbon\Carbon::parse($request->input('month'))->startOfMonth();
            $previousMonth = $billingMonth->copy()->subMonth();

            $currentReadings = MeterReading::whereYear('reading_date', $billingMonth->year)
                ->whereMonth('reading_date', $billingMonth->month)
                ->get()
                ->keyBy('flat_id');

            if ($currentReadings->isEmpty()) {
                return redirect()->back()->withErrors([
                    'error' => 'No meter readings found for ' . $billingMonth->format('F Y') . '. Please log readings first.'
                ]);
            }

            $previousReadings = MeterReading::whereYear('reading_date', $previousMonth->year)
                ->whereMonth('reading_date', $previousMonth->month)
                ->get()
                ->keyBy('flat_id');

            DB::beginTransaction();

            Bill::where('bill_for_month', $billingMonth->toDateString())->delete();

            $totalUsedM3         = 0;
            $totalUsedKg         = 0;
            $calculatedTotalBill = 0;
            $flatCalculations    = [];

            // CRITICAL UPDATE: Fetch ONLINE flats only
            $allOnlineFlats = Flat::where('status', 'online')->get();

            foreach ($allOnlineFlats as $flat) {
                $current     = $currentReadings->get($flat->id);
                $currentUnit = $current ? $current->reading_unit : 0;

                $previous     = $previousReadings->get($flat->id);
                $previousUnit = $previous ? $previous->reading_unit : 0;

                $usedM3 = max(0, $currentUnit - $previousUnit);
                $usedKg = $usedM3 * $m3ToKgMultiplier;

                $inputPricePerKg = $request->input('price_per_kg');
                $flatAmountDue   = round($usedKg * $inputPricePerKg, 2);

                $totalUsedM3         += $usedM3;
                $totalUsedKg         += $usedKg;
                $calculatedTotalBill += $flatAmountDue;

                $flatCalculations[] = [
                    'flat_id'          => $flat->id,
                    'previous_reading' => $previousUnit,
                    'current_reading'  => $currentUnit,
                    'used_m3'          => $usedM3,
                    'used_kg'          => $usedKg,
                    'amount_due'       => $flatAmountDue,
                ];
            }

            $pricePerM3 = $totalUsedM3 > 0 ? ($calculatedTotalBill / $totalUsedM3) : 0;

            $bill = Bill::create([
                'name'           => 'LPG Gas Bill - ' . $billingMonth->format('F Y'),
                'bill_for_month' => $billingMonth->toDateString(),
                'price_per_kg'   => $inputPricePerKg,     
                'price_per_m3'   => $pricePerM3,          
                'total_used_m3'  => $totalUsedM3,
                'total_used_kg'  => $totalUsedKg,
                'total_bill'     => $calculatedTotalBill, 
            ]);

            foreach ($flatCalculations as $calc) {
                BillDetail::create([
                    'bill_id'          => $bill->id,
                    'flat_id'          => $calc['flat_id'],
                    'previous_reading' => $calc['previous_reading'],
                    'current_reading'  => $calc['current_reading'],
                    'used_m3'          => $calc['used_m3'],
                    'used_kg'          => $calc['used_kg'],
                    'amount_due'       => $calc['amount_due'], 
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
            $massKg = $volumeM3 * env('M3_TO_KG_CONVERSION_RATE', 2.04);

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

            // Dynamically calculate the building-wide outstanding due sum for this statement month
            $totalPendingDue = $bill->details->where('payment_status', 'unpaid')->sum('bill_for_month');

            return view('bills.show', compact('bill', 'totalPendingDue'));
            
        } catch (\Exception $e) {
            // Redirects back to your history view with a clear message
            return redirect()->route('bill-history')->withErrors([
                'error' => 'Could not find any generated bill records for ' . htmlspecialchars($dateString)
            ]);
        }
    }


    public function togglePayment($id)
    {
        // Find the targeted flat breakdown line item
        $detail = BillDetail::findOrFail($id);

        // Swap states cleanly
        $detail->payment_status = ($detail->payment_status === 'paid') ? 'unpaid' : 'paid';
        $detail->save();

        return redirect()->back()->with('success', 'Payment collections state adjusted successfully.');
    }


}
