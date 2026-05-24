@extends('layouts.layout')
@section('content')

<div class="features-section pb-10">
    <div class="container">
        <div class="fv-card">
            <div class="fv-card-label">Generated Bills</div>
            <div class="fv-list" style="margin-top:8px">
                
                <div class="fv-list-item">
                    <span class="fv-list-name">
                        @forelse($bills as $bill)
                            <a class="fv-pill green m-1" href="/bill-history/{{ $bill->bill_for_month->format('Y-M') }}" style="display: inline-block;">
                                {{ $bill->bill_for_month->format("Y-M") }}
                            </a>
                        @empty
                            <span class="text-muted fs-6">No bills generated yet.</span>
                        @endforelse
                    </span>
                </div>

            </div>
        </div>
    </div>
</div>

@stop