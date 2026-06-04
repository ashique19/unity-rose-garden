<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the application landing page with the latest billing snapshot.
     */
    public function index()
    {
        // 1. Find the single most recent bill record
        $latestBill = Bill::orderBy('bill_for_month', 'desc')->first();

        // 2. If a bill exists, dynamically trigger and return the BillGenerator's show method
        if ($latestBill) {
            // Format the date string exactly how BillGenerator@show expects it (e.g., "2026-05-01")
            $dateString = $latestBill->bill_for_month;
            
            return app(BillGenerator::class)->show($dateString);
        }

        // 3. Fallback: If no bills exist in the system yet, render the welcome page empty-state
        return view('welcome', ['latestBill' => null]);
    }
}