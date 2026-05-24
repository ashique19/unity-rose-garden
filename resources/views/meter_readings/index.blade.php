@extends('layouts.layout')
@section('content')

<div  class="features-section pb-10">
    <div class="container">
        <div class="fv-card">
            <div class="fv-card-label">Show Reading By Month</div>
            <div class="fv-list" style="margin-top:8px">
                
                <div class="fv-list-item">
                    <span class="fv-list-name">
                    @for($i=0; $i> -6; $i--)
                    <a class="fv-pill green m-1" href="/meter-readings?q={{ \Carbon\Carbon::now()->addMonths($i)->format('Y-M') }}" style="display: inline-block;">{{ \Carbon\Carbon::now()->addMonths($i)->format('Y-M') }}</a>
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
      <div class="section-label reveal">Gas Meter Reading | <em>{{ $request->has('q') ? $request->q : 'Latest Entry' }}</em></div>
      <div class="section-label reveal">
          <a href="meter-readings/create">
              <i class="fa-solid fa-plus"></i>
          </a>
      </div>
      <div class="section-sub reveal reveal-delay-1">
        
      <div class="card shadow-sm border-0">
          <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                  <thead class="table-light text-uppercase fs-7 small text-muted">
                      <tr>
                          <th class="ps-4">Flat Info</th>
                          <th>Reading Date</th>
                          <th>Reading Unit</th>
                          <th class="pe-4 text-end">Actions</th>
                      </tr>
                  </thead>
                  <tbody>
                    @if(count($readings) > 0)
                    @forelse($readings as $reading)
                        <tr>
                            <td class="ps-4 font-weight-medium">
                                {{-- Adjust 'name' or 'number' based on your Flat model attributes --}}
                                Flat {{ $reading->flat->name ?? $reading->flat_id }}
                            </td>
                            <td>{{ \Carbon\Carbon::parse($reading->reading_date)->format('M d, Y') }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1.5 fs-6 fw-normal">
                                    {{ number_format($reading->reading_unit, 2) }} m<sup>3</sup>
                                </span>
                            </td>
                            <td class="pe-4 text-end" width="200">
                                    
                                    <form action="meter-readings/{{ $reading->id }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this reading?');">
                                        @csrf
                                        <a href="/meter-readings/{{ $reading->id }}/edit" class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <p class="mb-0">No meter readings found. Get started by adding the first one!</p>
                            </td>
                        </tr>
                    @endforelse
                        <tr>
                            <td class="ps-4 font-weight-medium">Total</td>
                            <td colspan="2">
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1.5 fs-6 fw-normal">
                                {{ $readings->sum('reading_unit') }} m<sup>3</sup>
                                </span>
                            </td>
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