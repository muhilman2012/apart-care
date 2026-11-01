<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::orderBy('role')->orderBy('name')->get(),
            'roleCounts' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['managedUser' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['change_password']);
        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['managedUser' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        if (! $request->boolean('change_password') || blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        unset($data['change_password']);
        if ($request->user()->is($user)) {
            $data['is_active'] = true;
            $data['role'] = 'manager';
        }
        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:resident,tro,technician,manager'],
            'is_active' => ['required', 'boolean'],
            'change_password' => [$user ? 'required' : 'nullable', 'boolean'],
            'password' => [
                $user ? Rule::requiredIf($request->boolean('change_password')) : 'required',
                'nullable',
                'string',
                Password::min(12)->letters()->numbers(),
                'confirmed',
            ],
        ]);
    }
}
