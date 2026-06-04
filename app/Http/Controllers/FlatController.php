<?php
namespace App\Http\Controllers;

use App\Models\Flat;
use Illuminate\Http\Request;

class FlatController extends Controller
{
    
    // 1. List all flats with natural sorting
    public function index()
    {
        $flats = Flat::orderByRaw('LENGTH(name) ASC')
            ->orderBy('name', 'ASC')
            ->get();

        return view('flats.index', compact('flats'));
    }

    // 2. Show the edit form for a single flat
    public function edit($id)
    {
        $flat = Flat::findOrFail($id);
        return view('flats.edit', compact('flat'));
    }

    // 3. Process the status switch update
    public function update(Request $request, $id)
    {
        $flat = Flat::findOrFail($id);

        $request->validate([
            'status' => 'required|in:online,offline',
        ]);

        $flat->update([
            'status' => $request->input('status')
        ]);

        return redirect()->route('flats.index')
            ->with('success', "Flat {$flat->name} status updated successfully.");
    }

    // 4. Show the latest 12 billing line items for a specific flat
    public function show($id)
    {
        // Find the flat or fail instantly with a 404
        $flat = Flat::findOrFail($id);

        // Fetch the 12 latest statements linked to this flat
        $billingHistory = \App\Models\BillDetail::where('flat_id', $id)
            ->orderBy('bill_for_month', 'desc')
            ->take(12)
            ->get();

        return view('flats.show', compact('flat', 'billingHistory'));
    }

}