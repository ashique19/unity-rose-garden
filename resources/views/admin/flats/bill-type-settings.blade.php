@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <h1 class="fw-bold text-dark mb-1">Flat × bill type settings</h1>
        <p class="text-muted mb-4">Enable or disable participation per flat and charge type.</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

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
