<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->get();

        $roles = Role::query()->orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'digits:11', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:4', 'max:100'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
        ]);

        $roleIds = Role::query()
            ->whereIn('name', $data['roles'])
            ->pluck('id')
            ->all();

        $user->roles()->sync($roleIds);

        Auditor::log('user.created', $user, [
            'phone' => $user->phone,
            'roles' => $data['roles'],
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User '.$user->name.' created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => [
                'required',
                'digits:11',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        if (
            $user->isAdmin()
            && ! in_array('admin', $data['roles'], true)
            && $this->adminCount() <= 1
        ) {
            return back()->withErrors([
                'roles' => 'Cannot remove the last admin role.',
            ])->withInput();
        }

        $user->name = $data['name'];
        $user->phone = $data['phone'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        $roleIds = Role::query()
            ->whereIn('name', $data['roles'])
            ->pluck('id')
            ->all();
        $user->roles()->sync($roleIds);

        Auditor::log('user.updated', $user, [
            'phone' => $user->phone,
            'roles' => $data['roles'],
            'password_changed' => ! empty($data['password']),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User '.$user->name.' updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        if ($user->isAdmin() && $this->adminCount() <= 1) {
            return back()->withErrors(['user' => 'Cannot delete the last admin user.']);
        }

        $name = $user->name;
        Auditor::log('user.deleted', $user, [
            'phone' => $user->phone,
        ]);

        $user->roles()->detach();
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User '.$name.' removed.');
    }

    private function adminCount(): int
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->count();
    }
}
