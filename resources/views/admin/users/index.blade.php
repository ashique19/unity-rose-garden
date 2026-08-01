@extends('layouts.layout')

@section('content')
<div class="features-section pt-20 pb-20">
    <div class="container">
        <h1 class="fw-bold text-dark mb-1">Users</h1>
        <p class="text-muted mb-4">Add or remove users and assign roles (admin, secretary, treasurer, member).</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="bg-white border rounded-3 shadow-sm p-4 mb-4">
            <h2 class="h5 fw-bold mb-3">Add user</h2>
            <form method="post" action="{{ route('admin.users.store') }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Phone (11 digits)</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}"
                           inputmode="numeric" pattern="[0-9]{11}" maxlength="11" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="4">
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">Roles</label>
                    @foreach($roles as $role)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="roles[]"
                                   value="{{ $role->name }}" id="new-role-{{ $role->name }}"
                                   @checked(collect(old('roles', ['admin']))->contains($role->name))>
                            <label class="form-check-label" for="new-role-{{ $role->name }}">{{ $role->label }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Add user</button>
                </div>
            </form>
        </div>

        <div class="table-responsive bg-white border rounded-3 shadow-sm">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>New password</th>
                        <th>Roles</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <form method="post" action="{{ route('admin.users.update', $user) }}" id="user-{{ $user->id }}">
                                @csrf
                                @method('PUT')
                            </form>
                            <td>
                                <input form="user-{{ $user->id }}" type="text" name="name"
                                       class="form-control form-control-sm"
                                       value="{{ old('name', $user->name) }}" required>
                            </td>
                            <td>
                                <input form="user-{{ $user->id }}" type="text" name="phone"
                                       class="form-control form-control-sm"
                                       value="{{ old('phone', $user->phone) }}"
                                       inputmode="numeric" pattern="[0-9]{11}" maxlength="11" required>
                            </td>
                            <td>
                                <input form="user-{{ $user->id }}" type="password" name="password"
                                       class="form-control form-control-sm" placeholder="Leave blank" minlength="4">
                            </td>
                            <td>
                                @foreach($roles as $role)
                                    <div class="form-check form-check-inline">
                                        <input form="user-{{ $user->id }}" class="form-check-input" type="checkbox"
                                               name="roles[]" value="{{ $role->name }}"
                                               id="user-{{ $user->id }}-{{ $role->name }}"
                                               @checked($user->roles->contains('name', $role->name))>
                                        <label class="form-check-label" for="user-{{ $user->id }}-{{ $role->name }}">
                                            {{ $role->label }}
                                        </label>
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-nowrap">
                                <button form="user-{{ $user->id }}" class="btn btn-sm btn-outline-primary">Save</button>
                                @if(auth()->id() !== $user->id)
                                    <form method="post" action="{{ route('admin.users.destroy', $user) }}" class="d-inline"
                                          onsubmit="return confirm('Remove user {{ $user->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
