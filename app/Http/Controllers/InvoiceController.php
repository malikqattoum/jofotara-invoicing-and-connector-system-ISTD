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

        $validated['uuid'] = $validated['uuid'] ?? (string) \Illuminate\Support\Str::uuid();

        // Generate hash of invoice data (for example, using SHA256 of JSON)
        $hash = hash('sha256', json_encode($validated));
        $validated['hash'] = $hash;

        // Generate QR code (for example, encode invoice number, uuid, and hash)
        $qrData = $validated['invoice_number'] . '|' . $validated['uuid'] . '|' . $hash;
        $qrCode = new QrCode($qrData);
        $writer = new PngWriter();
        $qrImage = $writer->write($qrCode, null, null, ['size' => 200]);
        $qrBase64 = base64_encode($qrImage->getString());
        $validated['qr_code'] = $qrBase64;

        $invoice = Invoice::create($validated);

        foreach ($validated['items'] as $item) {
            $invoice->items()->create($item);
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
