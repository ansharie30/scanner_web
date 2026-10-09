<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/users/Index', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email', 'role']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/Form', [
            'user' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['user', 'super_admin'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = new User();
        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ])->save();

        return redirect('/admin/users');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/Form', [
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['user', 'super_admin'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        // Do not let a super admin change their own role (prevents locking yourself out)
        if ($request->user()->id !== $user->id) {
            $user->role = $data['role'];
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect('/admin/users');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->id === $user->id, 403, 'You cannot delete your own account.');

        $user->delete();

        return redirect('/admin/users');
    }
}