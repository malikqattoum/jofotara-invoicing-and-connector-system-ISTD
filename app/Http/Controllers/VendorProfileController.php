<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\IntegrationSetting;

class VendorProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        return view('vendor-profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'organization_name' => 'required|string|max:255',
            'organization_address' => 'nullable|string|max:255',
            'organization_phone' => 'nullable|string|max:50',
            'tax_number' => 'nullable|string|max:100',
            'private_key' => 'nullable|file|mimes:pem',
            'public_cert' => 'nullable|file|mimes:pem',
        ]);

        // Remove file uploads from user data since they're handled separately
        $userUpdateData = collect($validated)->except(['private_key', 'public_cert'])->toArray();
        $user->update($userUpdateData);

        $integration = IntegrationSetting::where('organization_id', $user->id)->first();
        if ($integration) {
            $updateData = [];
            if ($request->hasFile('private_key')) {
                $updateData['private_key_path'] = $request->file('private_key')->store('certs/'.$user->id, 'local');
            }
            if ($request->hasFile('public_cert')) {
                $updateData['public_cert_path'] = $request->file('public_cert')->store('certs/'.$user->id, 'local');
            }
            if ($updateData) {
                $integration->update($updateData);
            }
        }
        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
}
