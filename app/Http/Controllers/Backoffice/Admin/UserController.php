<?php

namespace App\Http\Controllers\Backoffice\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the users for the admin backoffice.
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('backoffice.admin.management', ['tab' => 'users']);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|alpha_dash|unique:'.User::class,
            'google_id' => 'nullable|string|max:255|unique:'.User::class,
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'phone' => 'nullable|string|max:20|regex:/^[0-9]{4,20}$/',
            'address' => 'nullable|string|max:255',
            'email_verified' => 'nullable|boolean',
            'role' => 'required|in:'.implode(',', [User::ROLE_ADMIN, User::ROLE_SUPPLIER, User::ROLE_USER]),
            'password' => ['nullable', 'confirmed'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            $data['password'] = Hash::make(
                \Illuminate\Support\Str::random(12)
            );
        }

        if (! empty($data['email_verified'])) {
            $data['email_verified_at'] = now();
            unset($data['email_verified']);
        }

        User::create($data);

        return redirect()->route('backoffice.admin.users.index')->with('success', 'Utilisateur créé.');
    }

    /**
     * Display a specific user.
     */
    public function show(User $user): Response
    {
        return Inertia::render('Backoffice/Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('Backoffice/Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|alpha_dash|unique:'.User::class.',username,'.$user->id,
            'google_id' => 'nullable|string|max:255|unique:'.User::class.',google_id,'.$user->id,
            'email' => 'required|string|email|max:255|unique:'.User::class.',email,'.$user->id,
            'phone' => 'nullable|string|max:20|regex:/^[0-9]{4,20}$/',
            'address' => 'nullable|string|max:255',
            'email_verified' => 'nullable|boolean',
            'role' => 'required|in:'.implode(',', [User::ROLE_ADMIN, User::ROLE_SUPPLIER, User::ROLE_USER]),
            'password' => ['nullable', 'confirmed'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (array_key_exists('email_verified', $data)) {
            if (! empty($data['email_verified'])) {
                $data['email_verified_at'] = now();
            } else {
                $data['email_verified_at'] = null;
            }
            unset($data['email_verified']);
        }

        $user->update($data);

        return redirect()->route('backoffice.admin.users.index')->with('success', 'Utilisateur mis à jour.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $authUser = $request->user();

        // Prevent deleting yourself
        if ($authUser && $authUser->id === $user->id) {
            return redirect()->back()->withErrors(['user' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        // Prevent deleting last admin
        if ($user->role === User::ROLE_ADMIN) {
            $adminCount = User::query()->where('role', User::ROLE_ADMIN)->count();
            if ($adminCount <= 1) {
                return redirect()->back()->withErrors(['user' => 'Impossible de supprimer le dernier administrateur.']);
            }
        }

        $user->delete();

        return redirect()->route('backoffice.admin.management', ['tab' => 'users'])->with('success', 'Utilisateur supprimé.');
    }
}
