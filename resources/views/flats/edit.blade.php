@extends('layouts.layout')
@section('content')

<div class="container pt-5 pb-5 mt-20">
    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <div class="mb-3">
                <a href="{{ route('flats.index') }}" class="text-decoration-none text-secondary">← Back to Directory</a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0">Modify Status for Flat {{ $flat->name }}</h5>
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('flats.update', $flat->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="status" class="form-label font-weight-bold text-secondary">Operational Billing Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="online" {{ $flat->status == 'online' ? 'selected' : '' }}>
                                    Online (Include in upcoming calculations)
                                </option>
                                <option value="offline" {{ $flat->status == 'offline' ? 'selected' : '' }}>
                                    Offline (Exclude completely from generation runs)
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary py-2.5">
                                Save Status Changes
                            </button>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

@stop