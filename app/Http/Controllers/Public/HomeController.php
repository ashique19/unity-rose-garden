<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Flat;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $flats = $this->sortedFlats(Flat::query()->get());

        return view('public.home', compact('flats'));
    }

    private function sortedFlats(Collection $flats): Collection
    {
        return $flats->sortBy(function (Flat $flat) {
            preg_match('/^(\d+)([A-Z])$/i', $flat->name, $m);

            return [isset($m[1]) ? (int) $m[1] : 0, $m[2] ?? $flat->name];
        })->values();
    }
}
