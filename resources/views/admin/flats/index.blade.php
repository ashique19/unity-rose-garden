@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <h1 class="fw-bold text-dark mb-1">Flats</h1>
        <p class="text-muted mb-4">Update flat name, resident contact, phone, and online/offline status.</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="table-responsive bg-white border rounded-3 shadow-sm">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Flat</th>
                        <th>Contact name</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($flats as $flat)
                        <tr>
                            <form method="post" action="{{ route('admin.flats.update', $flat) }}" id="flat-{{ $flat->id }}">
                                @csrf
                                @method('PUT')
                            </form>
                            <td>
                                <input form="flat-{{ $flat->id }}" type="text" name="name"
                                       class="form-control form-control-sm" value="{{ old('name', $flat->name) }}"
                                       required maxlength="20" style="max-width: 5.5rem;">
                            </td>
                            <td>
                                <input form="flat-{{ $flat->id }}" type="text" name="contact_name"
                                       class="form-control form-control-sm"
                                       value="{{ old('contact_name', $flat->contact_name) }}" maxlength="120">
                            </td>
                            <td>
                                <input form="flat-{{ $flat->id }}" type="text" name="phone" inputmode="numeric"
                                       class="form-control form-control-sm"
                                       value="{{ old('phone', $flat->phone) }}"
                                       pattern="[0-9]{11}" maxlength="11" placeholder="01XXXXXXXXX">
                            </td>
                            <td>
                                <select form="flat-{{ $flat->id }}" name="status" class="form-select form-select-sm" required>
                                    <option value="online" @selected(old('status', $flat->status) === 'online')>Online</option>
                                    <option value="offline" @selected(old('status', $flat->status) === 'offline')>Offline</option>
                                </select>
                            </td>
                            <td>
                                <button form="flat-{{ $flat->id }}" class="btn btn-sm btn-outline-primary">Save</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
