@extends('layouts.layout')
@section('content')

<div  class="features-section pb-10">
    <div class="container">
        <div class="fv-card">
            <div class="fv-card-label">Show Reading By Month</div>
            <div class="fv-list" style="margin-top:8px">
                
                <div class="fv-list-item">
                    <span class="fv-list-name">
                    Flats
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="container pt-5 pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Flats Management Directory</h2>
        <small class="text-muted">Offline flats are automatically excluded from bill generation metrics.</small>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Flat ID</th>
                        <th>Flat Name</th>
                        <th>Billing System Status</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($flats as $flat)
                    <tr>
                        <td class="ps-4 text-muted">#{{ $flat->id }}</td>
                        <strong><td>Flat {{ $flat->name }}</td></strong>
                        <td>
                            @if($flat->status === 'online')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded">Active Billing (Online)</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded">Suspended (Offline)</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end">
                            <a href="{{ route('flats.edit', $flat->id) }}" class="btn btn-sm btn-outline-primary px-3">
                                Edit Status
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@stop