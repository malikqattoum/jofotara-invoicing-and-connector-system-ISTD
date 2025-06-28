<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'private_key' => 'required|file|mimes:pem',
            'public_cert' => 'required|file|mimes:pem',
        ]);

        $user = Auth::user();
        $integration = IntegrationSetting::where('organization_id', $user->organization_id)->firstOrFail();

        $privateKeyPath = $request->file('private_key')->store('certs/'.$user->organization_id, 'local');
        $publicCertPath = $request->file('public_cert')->store('certs/'.$user->organization_id, 'local');

        $integration->update([
            'private_key_path' => $privateKeyPath,
            'public_cert_path' => $publicCertPath,
        ]);

        return response()->json(['message' => 'Certificates uploaded successfully']);
    }
}
