<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::withCount('complaints')->latest()->get();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());

        session()->flash('success', "Pengguna {$user->name} berhasil ditambahkan.");

        return redirect()->route('admin.master.users.index');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        if ($user->is(auth()->user()) && $request->role !== $user->role) {
            abort(403, 'Anda tidak dapat mengubah peran (role) akun sendiri.');
        }

        $data = $request->safe()->except(['password']);

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        session()->flash('success', 'Pengguna berhasil diperbarui.');

        return redirect()->route('admin.master.users.index');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            abort(403, 'Anda tidak dapat menghapus akun sendiri.');
        }

        // Atomic with the User::deleting sweep (complaint rows + sessions);
        // files already unlinked by a failed attempt are gone by design.
        DB::transaction(fn () => $user->delete());

        session()->flash('success', 'Pengguna berhasil dihapus.');

        return redirect()->route('admin.master.users.index');
    }
}
