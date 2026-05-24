@extends('layouts.layout')
@section('content')

<!-- ── 4. FEATURES ── -->
<section class="features-section pb-0 batch-form-container">
  <div class="container">
    <div class="features-header">
      <div class="section-label reveal">Add <em>Reading</em></div>
      <div class="section-label reveal">
          <a href="/meter-readings">Old Records</a>
      </div>
      <div class="section-sub reveal reveal-delay-1">

      <div class="table-responsive">
        <h2>Record reading for : {{ date('Y-m-d') }}</h2>
        <table class="table">
          <tbody>
            @foreach($flats as $flat)
              <tr>
                <td>{{ $flat->name }}</td>
                <td width="200">
                  <form action="/meter-readings" method="POST">
                  @csrf
                  <input type="hidden" name="flat_id" value="{{ $flat->id }}" />
                  <input type="hidden" name="reading_date" value="{{ date('Y-m-d') }}" />
                  <div class="input-group">
                      <input type="number" step="0.01" class="form-control @error('reading_unit') is-invalid @enderror" name="reading_unit" placeholder="0.00" value="{{ old('reading_unit') }}" required>
                      <span class="input-group-text">m³</span>
                  </div>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <div class="cta-actions">
            <button type="button" class="btn-cta-primary save-data">
                Save
                <span>→</span>
            </button>
        </div>
      </div>
        
      <p class="section-sub reveal reveal-delay-2">
        <a href="/meter-readings">Old Records</a>
      </p>
    </div>

</section>

<section class="features-section pt-0">
  <div class="container">
    <div class="features-header mb-0">
      <div class="section-label reveal">Add <em>Single Reading</em></div>
    </div>

    <div class="section-sub reveal reveal-delay-1">
      <form action="/meter-readings" method="POST">
        @csrf
        
        <div class="mb-3">
          <label for="flat_id" class="form-label font-weight-bold">Select Flat</label>
          <select class="form-select @error('flat_id') is-invalid @enderror" id="flat_id" name="flat_id" required>
              <option value="" selected disabled>Choose a flat...</option>
              @foreach($flats as $flat)
                  <option value="{{ $flat->id }}" {{ old('flat_id') == $flat->id ? 'selected' : '' }}>
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
          <input type="date" class="form-control @error('reading_date') is-invalid @enderror" id="reading_date" name="reading_date" value="{{ old('reading_date', date('Y-m-d')) }}" required>
          @error('reading_date')
              <div class="invalid-feedback">{{ $message }}</div>
          @enderror
      </div>

      <div class="mb-4">
          <label for="reading_unit" class="form-label">Reading Unit</label>
          <div class="input-group">
              <input type="number" step="0.01" class="form-control @error('reading_unit') is-invalid @enderror" id="reading_unit" name="reading_unit" placeholder="0.00" value="{{ old('reading_unit') }}" required>
              <span class="input-group-text">m³</span>
          </div>
          @error('reading_unit')
              <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror
      </div>
        
        <div class="cta-actions">
            <button type="submit" class="btn-cta-primary">
                Save
                <span>→</span>
            </button>
        </div>
      </form>
    </div>
  </div>
</section>

@stop

@section('js')

<script>
    $(document).ready(function(){
      $('.batch-form-container form').submit(function(e){ e.preventDefault(); })
      $('.save-data').click(function(e) {
        e.preventDefault();
        let that = $(this),
            input_pending = 0;
        that.text('saving...');

        $('.batch-form-container [name="reading_unit"]').each(function(i,v){
          if($(this).val().length == 0){
            input_pending++;
          }
        })

        if(input_pending > 0){
          that.text('Entry Pending. Retry?')
        } else{

          let totalForms = $('.batch-form-container form').length;
          let successCount = 0;

          // Use jQuery's .each() to loop through forms safely
          $('form').each(function() {
            let form = $(this); // Wraps the current form securely in jQuery
            let url = form.attr('action');
            let data = form.serializeArray();

            $.post(url, data, function(result) {
              if (Number(result) === 1) {
                successCount++;
                
                // Safely targets the DOM container to remove it
                form.parent().parent().remove();
                
                // Dynamically updates the button text with the total progress
                that.text('Saved...' + successCount + '/' + totalForms);
              }
            });
          });
        }
      });
      
    })
  </script>

  @stop