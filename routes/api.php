<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JoFotaraController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\CertificateController;

Route::post('/invoices/{id}/submit', [JoFotaraController::class, 'submitToTaxSystem']);

Route::post('/invoices', [InvoiceController::class, 'store']);
Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
Route::get('/invoices/status/{id}', [InvoiceController::class, 'status']);
Route::post('/webhooks/invoice-status', [JoFotaraController::class, 'handleWebhook']);

Route::post('/vendors/register', [VendorController::class, 'register']);
Route::post('/vendors/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);
    $user = \App\Models\User::where('email', $request->email)->first();
    if (! $user || ! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
    $token = $user->createToken('api-token')->plainTextToken;
    return response()->json(['token' => $token, 'user' => $user]);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/vendors/profile', [VendorController::class, 'profile']);
    Route::put('/vendors/profile', [VendorController::class, 'updateProfile']);
    Route::post('/certificates/upload', [CertificateController::class, 'upload']);
    Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'downloadPdf']);
    Route::get('/invoices/{id}/xml', [InvoiceController::class, 'downloadXml']);
});
