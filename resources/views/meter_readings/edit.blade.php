@extends('layouts.layout')
@section('content')
<!-- ── 4. FEATURES ── -->
<section class="features-section" id="features">
  <div class="container">
    <div class="features-header">
      <div class="section-label reveal">
          <a href="/meter-readings">
              <i class="fa-solid fa-chevron-left"></i>
          </a>
      </div>
      <div class="section-label reveal">Modify <em>Gas Meter Reading</em></div>
      <div class="section-label reveal">
          <a href="/meter-readings/create">
              <i class="fa-solid fa-plus"></i>
          </a>
      </div>
      <div class="section-sub reveal reveal-delay-1">
        
          <form action="/meter-readings/{{$meterReading->id}}" method="POST">
              @csrf
              @method('PUT')

              <div class="mb-3">
                  <label for="flat_id" class="form-label">Select Flat</label>
                  <select class="form-select @error('flat_id') is-invalid @enderror" id="flat_id" name="flat_id" required>
                      @foreach($flats as $flat)
                          <option value="{{ $flat->id }}" {{ old('flat_id', $meterReading->flat_id) == $flat->id ? 'selected' : '' }}>
                              Flat {{ $flat->name }}
                          </option>
                      @endforeach
                  </select>
                  @error('flat_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
              </div>

              <div class="mb-3">
                  <label for="reading_date" class="form-label">Reading Date</label>
                  <input type="date" 
                          class="form-control @error('reading_date') is-invalid @enderror" 
                          id="reading_date" 
                          name="reading_date" 
                          value="{{ old('reading_date', \Carbon\Carbon::parse($meterReading->reading_date)->format('Y-m-d')) }}"                            
                          required>
                  @error('reading_date')
                      <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
              </div>

              <div class="mb-4">
                  <label for="reading_unit" class="form-label">Reading Unit</label>
                  <div class="input-group">
                      <input type="number" step="0.01" class="form-control @error('reading_unit') is-invalid @enderror" id="reading_unit" name="reading_unit" value="{{ old('reading_unit', $meterReading->reading_unit) }}" required>
                      <span class="input-group-text">m³</span>
                  </div>
                  @error('reading_unit')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
              </div>

              <div class="d-flex justify-content-end gap-2">
                  <a href="{{ route('meter-readings.index') }}" class="btn-cta-ghost">Discard Changes</a>
                  <button type="submit" class="btn-cta-primary">
                      Save
                      <span>→</span>
                  </button>
              </div>
          </form>
    </div>

</section>

  @stop