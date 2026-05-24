<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the application landing page with the latest billing snapshot and detailed breakdown.
     */
    public function index()
    {
        // Fetch the single most recent bill and eager load its child details and flat names
        $latestBill = Bill::orderBy('bill_for_month', 'desc')
            ->with(['details.flat'])
            ->first();

        return view('welcome', compact('latestBill'));
    }
}