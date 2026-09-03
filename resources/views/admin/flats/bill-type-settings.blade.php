@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <h1 class="fw-bold text-dark mb-1">Flat × bill type settings</h1>
        <p class="text-muted mb-4">Enable or disable participation per flat and charge type. Add a new type to include it in generate and other charges.</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <x-mobile-panel-toggles :add-open="$errors->any()">
            <x-slot:add>
                <div class="bg-white border rounded-3 shadow-sm p-4 mb-4">
                    <h2 class="h5 fw-bold mb-3">Add bill type</h2>
                    <form method="post" action="{{ route('admin.flat-bill-type-settings.store-bill-type') }}" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label" for="bill_type_label">Label</label>
                            <input type="text" name="label" id="bill_type_label" class="form-control"
                                   value="{{ old('label') }}" required maxlength="120" placeholder="e.g. Security">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="bill_type_key">Key (optional)</label>
                            <input type="text" name="key" id="bill_type_key" class="form-control"
                                   value="{{ old('key') }}" maxlength="80" placeholder="auto from label">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="bill_type_amount">Default amount (optional)</label>
                            <input type="number" name="default_amount" id="bill_type_amount" class="form-control"
                                   value="{{ old('default_amount') }}" min="0" step="0.01" placeholder="৳">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100">Add</button>
                        </div>
                        <div class="col-12">
                            <p class="text-muted small mb-0">New types apply to all flats (enabled). If you set a default amount, a building-wide charge template is created so generate includes it.</p>
                        </div>
                    </form>
                </div>
            </x-slot:add>
        </x-mobile-panel-toggles>

        <form method="post" action="{{ route('admin.flat-bill-type-settings.update') }}">
            @csrf
            @method('PUT')

            <div class="table-responsive bg-white border rounded-3 shadow-sm">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Flat</th>
                            @foreach($billTypes as $type)
                                <th class="text-center">{{ $type->label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($flats as $flat)
                            <tr>
                                <td class="fw-semibold">{{ $flat->name }}</td>
                                @foreach($billTypes as $type)
                                    @php
                                        $setting = $flat->billTypeSettings->firstWhere('bill_type_id', $type->id);
                                        $checked = $setting?->enabled ?? true;
                                        $key = $flat->id.'_'.$type->id;
                                    @endphp
                                    <td class="text-center">
                                        <input type="checkbox"
                                               name="enabled[{{ $key }}]"
                                               value="1"
                                               @checked($checked)>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-primary mt-4">Save settings</button>
        </form>
    </div>
</div>
@endsection
