@extends('layouts.layout')
@section('content')

<div class="features-section pt-20 pb-20">
    <div class="container">
        <div class="fv-card">
            <div class="fv-card-label">Generate Bill</div>
            <div class="fv-list" style="margin-top:8px">
                
                <div class="fv-list-item">
                    <span class="fv-list-name">

                        <form action="/generate-bill" method="POST">
                            @csrf
                            
                            @if($errors->has('error'))
                                <div class="alert alert-danger role="alert">
                                    {{ $errors->first('error') }}
                                </div>
                            @endif

                            @if(session('success'))
                                <div class="alert alert-success" role="alert">
                                    {{ session('success') }}
                                </div>
                            @endif
                            
                            <div class="mb-3">
                                <label for="month" class="form-label font-weight-bold">Select Month</label>
                                <select class="form-select @error('month') is-invalid @enderror" id="month" name="month" required>
                                    <option value="" selected disabled>Choose a Month...</option>
                                    @foreach($dropdownOptions as $option)
                                        <option value="{{ $option['value'] }}" {{ request('bill_month') == $option['value'] ? 'selected' : '' }}>
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('month')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="price_per_kg" class="form-label">Price per KG</label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('price_per_kg') is-invalid @enderror" id="price_per_kg" name="price_per_kg" placeholder="0.00" value="{{ old('price_per_kg') }}" required>
                                    <span class="input-group-text">Tk</span>
                                </div>
                                @error('price_per_kg')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="cta-actions">
                                <button type="submit" class="btn-cta-primary">
                                    Generate
                                    <span>→</span>
                                </button>
                            </div>
                        </form>

                    </span>
                </div>
            </div>
        </div>
    
    </div>
</div>

@stop