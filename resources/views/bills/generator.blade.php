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
                                    @for($i=0; $i > -6; $i--)
                                    <option value="{{ \Carbon\Carbon::now()->addMonths($i)->format('Y-M') }}">
                                        {{ \Carbon\Carbon::now()->addMonths($i)->format('Y-M') }}
                                    </option>
                                    @endfor
                                </select>
                                @error('month')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="total_bill" class="form-label">Total Bill</label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('total_bill') is-invalid @enderror" id="total_bill" name="total_bill" placeholder="0.00" value="{{ old('total_bill') }}" required>
                                    <span class="input-group-text">Tk</span>
                                </div>
                                @error('total_bill')
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