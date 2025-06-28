<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'organization_name' => 'required|string|max:255',
            'organization_address' => 'nullable|string|max:255',
            'organization_phone' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'organization_name' => $validated['organization_name'],
            'organization_address' => $validated['organization_address'] ?? null,
            'organization_phone' => $validated['organization_phone'] ?? null,
            'tax_number' => $validated['tax_number'] ?? null,
            'role' => 'vendor',
        ]);

        return response()->json(['message' => 'Vendor registered successfully', 'user' => $user], 201);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json($user);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'organization_name' => 'sometimes|required|string|max:255',
            'organization_address' => 'nullable|string|max:255',
            'organization_phone' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:100',
        ]);
        $user->update($validated);
        return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);
    }
}
