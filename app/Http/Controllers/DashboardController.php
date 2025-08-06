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

        // Build query to get user's invoices - check both vendor_id and organization_id
        $query = Invoice::where(function($q) use ($user) {
            $q->where('vendor_id', $user->id);
            if ($user->organization_id) {
                $q->orWhere('organization_id', $user->organization_id);
            }
        });

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

        // Statistics for dashboard cards - use same logic
        $statsQuery = Invoice::where(function($q) use ($user) {
            $q->where('vendor_id', $user->id);
            if ($user->organization_id) {
                $q->orWhere('organization_id', $user->organization_id);
            }
        });

        $stats = [
            'total' => $statsQuery->count(),
            'submitted' => (clone $statsQuery)->where('status', 'submitted')->count(),
            'rejected' => (clone $statsQuery)->where('status', 'rejected')->count(),
            'draft' => (clone $statsQuery)->where('status', 'draft')->count(),
        ];

        $invoices = $query->orderBy('created_at', 'desc')->take(10)->get();

        // Get integration settings for the user
        $integration = IntegrationSetting::where(function($q) use ($user) {
            $q->where('vendor_id', $user->id);
            if ($user->organization_id) {
                $q->orWhere('organization_id', $user->organization_id);
            }
        })->first();

        return view('dashboard', compact('user', 'invoices', 'integration', 'stats'));
    }
}
