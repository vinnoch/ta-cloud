<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'title' => 'Edit Profil',
            'heading' => 'Edit Profil',
            'crumbs' => strtoupper((string) $request->user()->role) . ' • PROFIL',
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'nim' => [$user->role === 'mahasiswa' ? 'nullable' : 'prohibited', 'nullable', 'string', 'max:50', Rule::unique('users', 'nim')->ignore($user->id)],
            'nidn_nip' => [$user->role === 'dosen' ? 'nullable' : 'prohibited', 'nullable', 'string', 'max:255', Rule::unique('users', 'nidn_nip')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($user->role === 'mahasiswa') {
            $user->nim = $validated['nim'] ?? null;
        }

        if ($user->role === 'dosen') {
            $user->nidn_nip = $validated['nidn_nip'] ?? null;
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
