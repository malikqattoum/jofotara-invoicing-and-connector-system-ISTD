<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations to add enhanced InvoiceQ-inspired features
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Customer information fields
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->string('customer_phone')->nullable()->after('customer_email');
            $table->text('customer_address')->nullable()->after('customer_phone');

            // Payment tracking fields
            $table->string('payment_status')->default('pending')->after('status');
            $table->date('due_date')->nullable()->after('invoice_date');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->string('payment_method')->nullable()->after('paid_at');
            $table->text('payment_reference')->nullable()->after('payment_method');

            // Integration and processing fields
            $table->unsignedBigInteger('vendor_id')->nullable()->after('organization_id');
            $table->string('integration_type')->nullable()->after('vendor_id');
            $table->timestamp('submitted_at')->nullable()->after('paid_at');
            $table->timestamp('processed_at')->nullable()->after('submitted_at');

            // Compliance and rejection tracking
            $table->text('rejection_reason')->nullable()->after('processed_at');
            $table->integer('revision_number')->default(1)->after('rejection_reason');
            $table->string('compliance_status')->default('pending')->after('revision_number');

            // Business intelligence fields
            $table->decimal('net_amount', 10, 2)->nullable()->after('total_amount');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('net_amount');
            $table->json('line_items_summary')->nullable()->after('discount_amount');
            $table->string('invoice_type')->default('standard')->after('line_items_summary');

            // Audit and tracking
            $table->json('audit_trail')->nullable()->after('invoice_type');
            $table->string('created_by')->nullable()->after('audit_trail');
            $table->string('updated_by')->nullable()->after('created_by');

            // Add indexes for performance
            $table->index(['vendor_id', 'status']);
            $table->index(['organization_id', 'payment_status']);
            $table->index(['due_date', 'payment_status']);
            $table->index(['customer_tax_number']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['vendor_id', 'status']);
            $table->dropIndex(['organization_id', 'payment_status']);
            $table->dropIndex(['due_date', 'payment_status']);
            $table->dropIndex(['customer_tax_number']);
            $table->dropIndex(['created_at', 'status']);

            // Drop added columns
            $table->dropColumn([
                'customer_email',
                'customer_phone',
                'customer_address',
                'payment_status',
                'due_date',
                'paid_at',
                'payment_method',
                'payment_reference',
                'vendor_id',
                'integration_type',
                'submitted_at',
                'processed_at',
                'rejection_reason',
                'revision_number',
                'compliance_status',
                'net_amount',
                'discount_amount',
                'line_items_summary',
                'invoice_type',
                'audit_trail',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
