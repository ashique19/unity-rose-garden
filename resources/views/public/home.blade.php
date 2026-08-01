@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <div class="mb-5 d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3 text-center text-md-start">
            <div>
                <h1 class="fw-bold text-dark mb-1">Flats</h1>
                <p class="text-muted fs-5 mb-0">Monthly flat statements</p>
            </div>
            <div>
                <a href="{{ route('public.statements.print-building', ['month' => $printMonth->format('Y-m')]) }}"
                   class="btn btn-outline-dark px-4 py-2">
                    Print building bills
                    <span class="text-muted small ms-1">({{ $printMonth->format('M Y') }})</span>
                </a>
            </div>
        </div>

        <div class="row g-3">
            @foreach($flats as $flat)
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="{{ route('public.flats.show', $flat) }}" class="text-decoration-none">
                        <div class="border rounded-3 p-4 h-100 bg-white shadow-sm text-center">
                            <div class="fw-bold fs-4 text-dark">{{ $flat->name }}</div>
                            <div class="text-muted small mt-1">View bills</div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
