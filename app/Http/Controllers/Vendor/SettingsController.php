<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Show the settings page.
     */
    public function index()
    {
        $vendor = Auth::user();
        $integrations = IntegrationSetting::where('vendor_id', $vendor->id)->get();

        return view('vendor.dashboard.settings', compact('vendor', 'integrations'));
    }

    /**
     * Update vendor settings.
     */
    public function update(Request $request)
    {
        $vendor = Auth::user();
        $settingsType = $request->input('settings_type');

        switch ($settingsType) {
            case 'profile':
                return $this->updateProfile($request, $vendor);
            case 'notifications':
                return $this->updateNotifications($request, $vendor);
            case 'password':
                return $this->updatePassword($request, $vendor);
            default:
                return $this->updateProfile($request, $vendor);
        }
    }

    /**
     * Update profile information.
     */
    private function updateProfile(Request $request, $vendor)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $vendor->id,
            'company_name' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $vendor->update([
            'name' => $request->name,
            'email' => $request->email,
            'company_name' => $request->company_name,
            'tax_number' => $request->tax_number,
            'address' => $request->address,
            'phone' => $request->phone,
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update notification preferences.
     */
    private function updateNotifications(Request $request, $vendor)
    {
        $notifications = $request->input('notifications', []);

        $currentSettings = $vendor->settings ?? [];
        $currentSettings['notifications'] = $notifications;

        $vendor->update(['settings' => $currentSettings]);

        return back()->with('success', 'Notification preferences updated successfully.');
    }

    /**
     * Update password.
     */
    private function updatePassword(Request $request, $vendor)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
            'new_password_confirmation' => 'required'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if (!Hash::check($request->current_password, $vendor->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $vendor->update([
            'password' => Hash::make($request->new_password)
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    /**
     * Show preferences page.
     */
    public function preferences()
    {
        $vendor = Auth::user();
        return view('vendor.settings.preferences', compact('vendor'));
    }

    /**
     * Update preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $vendor = Auth::user();

        $validator = Validator::make($request->all(), [
            'timezone' => 'nullable|string|max:255',
            'currency' => 'nullable|string|size:3',
            'date_format' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:255',
            'dashboard_layout' => 'nullable|array',
            'report_preferences' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $settings = $vendor->settings ?? [];
        $settings['preferences'] = $request->only([
            'timezone', 'currency', 'date_format', 'language',
            'dashboard_layout', 'report_preferences'
        ]);

        $vendor->update(['settings' => $settings]);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully.'
        ]);
    }

    /**
     * Show API settings page.
     */
    public function api()
    {
        $vendor = Auth::user();
        return view('vendor.settings.api', compact('vendor'));
    }

    /**
     * Generate API key.
     */
    public function generateApiKey(): JsonResponse
    {
        $vendor = Auth::user();
        $apiKey = $vendor->createToken('API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'api_key' => $apiKey,
            'message' => 'API key generated successfully.'
        ]);
    }

    /**
     * Revoke API key.
     */
    public function revokeApiKey($keyId): JsonResponse
    {
        $vendor = Auth::user();
        $vendor->tokens()->where('id', $keyId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'API key revoked successfully.'
        ]);
    }
}
