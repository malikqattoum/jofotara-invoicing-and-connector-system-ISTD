<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('items')->get();
        return response()->json($invoices);
    }

    public function show(Request $request, $id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        // Auditing: Log invoice view
        Log::info('Invoice viewed', [
            'user_id' => optional($request->user())->id,
            'ip' => $request->ip(),
            'invoice_id' => $invoice->id
        ]);
        return response()->json($invoice);
    }

    public function status(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        Log::info('Invoice status checked', [
            'user_id' => optional($request->user())->id,
            'ip' => $request->ip(),
            'invoice_id' => $invoice->id,
            'status' => $invoice->status
        ]);
        return response()->json(['status' => $invoice->status]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:invoices',
            'customer_name' => 'required|string',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'items' => 'required|array',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        // Get authenticated user
        $user = $request->user();

        // Add required fields with proper user association
        $validated['uuid'] = $validated['uuid'] ?? (string) \Illuminate\Support\Str::uuid();
        $validated['vendor_id'] = $user ? $user->id : null;
        $validated['organization_id'] = $user ? ($user->organization_id ?? 1) : 1;
        $validated['invoice_date'] = $validated['invoice_date'] ?? now()->format('Y-m-d');

        // Calculate totals from items
        $totalAmount = 0;
        $taxAmount = 0;
        foreach ($validated['items'] as $item) {
            $itemTotal = $item['quantity'] * $item['price'];
            $totalAmount += $itemTotal;
            // Assume 16% tax rate for Jordan (can be made configurable)
            $taxAmount += $itemTotal * 0.16;
        }
        $validated['total_amount'] = $totalAmount + $taxAmount;
        $validated['tax_amount'] = $taxAmount;

        // Generate hash of invoice data (for example, using SHA256 of JSON)
        $hash = hash('sha256', json_encode($validated));
        $validated['hash'] = $hash;

        // Generate QR code (for example, encode invoice number, uuid, and hash)
        $qrData = $validated['invoice_number'] . '|' . $validated['uuid'] . '|' . $hash;

        try {
            $qrCode = new QrCode($qrData);
            $writer = new PngWriter();
            $qrImage = $writer->write($qrCode, null, null, ['size' => 200]);
            $qrBase64 = base64_encode($qrImage->getString());
            $validated['qr_code'] = $qrBase64;
        } catch (\Exception $e) {
            // If QR code generation fails (e.g., GD extension not available), use a placeholder
            Log::warning('QR code generation failed: ' . $e->getMessage());
            $validated['qr_code'] = 'QR_CODE_GENERATION_FAILED';
        }

        $invoice = Invoice::create($validated);

        foreach ($validated['items'] as $item) {
            // Map API fields to database fields and calculate missing values
            $itemTotal = $item['quantity'] * $item['price'];
            $itemTax = $itemTotal * 0.16; // 16% tax rate for Jordan

            $invoice->items()->create([
                'item_name' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'tax' => $itemTax,
                'total' => $itemTotal + $itemTax
            ]);
        }

        // Auditing: Log invoice creation
        Log::info('Invoice created', [
            'user_id' => optional($request->user())->id,
            'ip' => $request->ip(),
            'invoice_id' => $invoice->id,
            'data' => $validated
        ]);

        return response()->json($invoice, 201);
    }

    public function downloadPdf(Request $request, $id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        // Render HTML view for invoice
        $html = view('xml.ubl-invoice', compact('invoice'))->render();
        // Use a simple PDF generator (e.g., dompdf)
        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html);
        return $pdf->download('invoice_' . $invoice->invoice_number . '.pdf');
    }

    public function downloadXml(Request $request, $id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        $xml = view('xml.ubl-invoice', compact('invoice'))->render();
        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="invoice_' . $invoice->invoice_number . '.xml"',
        ]);
    }
}
