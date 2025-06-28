<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\IntegrationSetting;
use App\Services\JoFotaraService;
use Illuminate\Support\Facades\Log;

class JoFotaraController extends Controller
{
    public function submitToTaxSystem(Request $request, $invoice_id)
    {
        $invoice = Invoice::with('items')->findOrFail($invoice_id);
        $integration = IntegrationSetting::where('organization_id', $invoice->organization_id)->first();
        $user = $request->user();
        if ($user && !$user->is_active) {
            return response()->json(['message' => 'Your account is disabled. Please contact admin.'], 403);
        }

        // Auditing: Log submission attempt
        Log::info('Invoice submitted to JoFotara', [
            'user_id' => optional($request->user())->id,
            'ip' => $request->ip(),
            'invoice_id' => $invoice->id
        ]);

        // Use the JoFotara service to submit the invoice
        $joFotaraService = new JoFotaraService($integration);
        $result = $joFotaraService->submitInvoice($invoice);

        // Update invoice status based on the result
        $invoice->update([
            'status' => $result['status'],
            'submission_response' => $result['response']
        ]);

        return response()->json(['status' => $invoice->status]);
    }

    public function handleWebhook(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|integer',
            'status' => 'required|string',
            'response' => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);
        $invoice->update([
            'status' => $validated['status'],
            'submission_response' => $validated['response'] ?? $invoice->submission_response,
        ]);

        // Auditing: Log webhook status update
        Log::info('Invoice status updated via webhook', [
            'invoice_id' => $invoice->id,
            'new_status' => $validated['status'],
            'ip' => $request->ip(),
        ]);

        return response()->json(['message' => 'Invoice status updated successfully']);
    }

}
