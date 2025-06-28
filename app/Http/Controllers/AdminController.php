<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $vendors = User::where('role', 'vendor')->get();
        return view('admin.vendors', compact('vendors'));
    }

    public function toggleVendor($id)
    {
        $vendor = User::where('role', 'vendor')->findOrFail($id);
        $vendor->is_active = !$vendor->is_active;
        $vendor->save();
        return redirect()->back()->with('success', 'Vendor status updated.');
    }
}
