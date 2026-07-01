@extends('layouts.layout')
@section('content')

<div class="features-section pb-10">
    <div class="container">
        <div class="fv-card">
            <div class="fv-card-label">Show Reading By Month</div>
            <div class="fv-list" style="margin-top:8px">
                <div class="fv-list-item">
                    <span class="fv-list-name">
                    @for($i=0; $i > -6; $i--)
                        @php 
                            $loopMonth = \Carbon\Carbon::now()->addMonths($i); 
                            $isSelected = $request->query('q') === $loopMonth->format('Y-M');
                        @endphp
                        <a class="fv-pill {{ $isSelected ? 'bg-primary text-white' : 'green' }} m-1" 
                           href="{{ route('meter-readings-and-charges.index', ['q' => $loopMonth->format('Y-M')]) }}" 
                           style="display: inline-block;">
                            {{ $loopMonth->format('Y-M') }}
                        </a>
                    @endfor
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── 4. FEATURES ── -->
<section class="features-section pt-0">
  <div class="container">
    <div class="features-header">
      <div class="section-label reveal">Gas Meter & Custom Charges Ledger | <em>{{ $request->has('q') ? $request->q : 'Latest Entry' }}</em></div>
      <div class="section-label reveal">
          <a href="{{ route('meter-readings-and-charges.create') }}" class="btn btn-primary btn-sm rounded-circle p-2">
              <i class="fa-solid fa-plus text-white"></i>
          </a>
      </div>
      <div class="section-sub reveal reveal-delay-1 mt-4">
        
      <div class="card shadow-sm border-0">
          <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                  <thead class="table-light text-uppercase fs-7 small text-muted">
                      <tr>
                          <th class="ps-4">Flat Info</th>
                          <th>Reading Date</th>
                          <th>Gas Reading</th>
                          <th>Ad-Hoc / Custom Charges</th>
                          <th class="pe-4 text-end">Actions</th>
                      </tr>
                  </thead>
                  <tbody>
                    @if(count($readings) > 0)
                    @forelse($readings as $reading)
                        <tr>
                            <td class="ps-4 font-weight-medium">
                                <strong>Flat {{ $reading->flat->name ?? $reading->flat_id }}</strong>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($reading->reading_date)->format('M d, Y') }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1.5 fs-6 fw-normal">
                                    {{ number_format($reading->reading_unit, 2) }} m<sup>3</sup>
                                </span>
                            </td>
                            <td class="py-2">
                                @if($reading->flat)
                                    @php
                                        // Parse the reading date for this specific row line item
                                        $readingDate = \Carbon\Carbon::parse($reading->reading_date);
                                        
                                        // Filter the eager-loaded relationship collection strictly for this row's month context
                                        $matchedCharges = $reading->flat->customCharges->filter(function($charge) use ($readingDate) {
                                            $chargeDate = \Carbon\Carbon::parse($charge->charge_month);
                                            return $chargeDate->year === $readingDate->year && $chargeDate->month === $readingDate->month;
                                        });
                                    @endphp

                                    @if($matchedCharges->count() > 0)
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($matchedCharges as $customCharge)
                                                <span class="badge bg-warning-subtle text-warning-emphasis px-2 py-1 border border-warning-subtle text-[11px]" title="{{ $customCharge->notes }}">
                                                    {{ $customCharge->label }}: ৳{{ number_format($customCharge->amount, 2) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted italic fs-7 small text-gray-400">— Standard Charges Only</span>
                                    @endif
                                @else
                                    <span class="text-muted italic fs-7 small text-gray-400">— Flat Unmapped</span>
                                @endif
                            </td>
                            <td class="pe-4 text-end" width="200">
                                <form action="{{ route('meter-readings-and-charges.destroy', $reading->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this reading?');">
                                    @csrf
                                    <a href="{{ route('meter-readings-and-charges.edit', $reading->id) }}" class="btn btn-sm btn-outline-primary">
                                        Edit
                                    </a>
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <p class="mb-0">No active billing records mapped for this month timeframe.</p>
                            </td>
                        </tr>
                    @endforelse
                        <tr class="table-light fw-bold">
                            <td class="ps-4">Total Summary</td>
                            <td></td>
                            <td>
                                <span class="badge bg-dark text-white px-2 py-1.5 fs-6">
                                    {{ number_format($readings->sum('reading_unit'), 2) }} m<sup>3</sup>
                                </span>
                            </td>
                            <td>
                                <!-- Sum up all fetched exceptional amounts inside current visible window -->
                                <span class="badge bg-primary text-white px-2 py-1.5 fs-6">
                                    ৳{{ number_format($readings->sum(function($r) { return $r->flat ? $r->flat->customCharges->sum('amount') : 0; }), 2) }}
                                </span>
                            </td>
                            <td></td>
                        </tr>
                    @endif
                  </tbody>
              </table>
          </div>
          @if($readings->hasPages())
              <div class="card-footer bg-white border-0 pt-3 pb-2 px-4">
                  {{ $readings->links() }}
              </div>
          @endif
      </div>
    </div>
</section>

@stop