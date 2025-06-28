<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Invoice::where('organization_id', $user->organization_id ?? null);

        // Filtering by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by invoice number or customer name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhere('customer_name', 'like', "%$search%")
                  ->orWhere('customer_email', 'like', "%$search%")
                  ->orWhere('customer_phone', 'like', "%$search%")
                  ;
            });
        }

        // Statistics for dashboard cards
        $stats = [
            'total' => Invoice::where('organization_id', $user->organization_id ?? null)->count(),
            'submitted' => Invoice::where('organization_id', $user->organization_id ?? null)->where('status', 'submitted')->count(),
            'rejected' => Invoice::where('organization_id', $user->organization_id ?? null)->where('status', 'rejected')->count(),
            'draft' => Invoice::where('organization_id', $user->organization_id ?? null)->where('status', 'draft')->count(),
        ];

        $invoices = $query->orderBy('created_at', 'desc')->take(10)->get();
        $integration = IntegrationSetting::where('organization_id', $user->organization_id ?? null)->first();
        return view('dashboard', compact('user', 'invoices', 'integration', 'stats'));
    }
}
