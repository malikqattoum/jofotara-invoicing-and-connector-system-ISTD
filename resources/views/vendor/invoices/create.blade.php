@extends('layouts.vendor')

@section('title', 'Create Invoice')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Create New Invoice</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('vendor.dashboard.index') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('vendor.invoices.index') }}">Invoices</a></li>
                            <li class="breadcrumb-item active">Create Invoice</li>
                        </ol>
                    </nav>
                </div>
                <a href="{{ route('vendor.invoices.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Invoices
                </a>
            </div>

            <form action="{{ route('vendor.invoices.store') }}" method="POST" id="invoiceForm">
                @csrf
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Invoice Details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Invoice Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="invoice_number" class="form-label">Invoice Number</label>
                                            <input type="text" class="form-control" id="invoice_number" name="invoice_number"
                                                value="{{ old('invoice_number', 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)) }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="invoice_date" class="form-label">Invoice Date</label>
                                            <input type="date" class="form-control" id="invoice_date" name="invoice_date"
                                                value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="due_date" class="form-label">Due Date</label>
                                            <input type="date" class="form-control" id="due_date" name="due_date"
                                                value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="currency" class="form-label">Currency</label>
                                            <select class="form-select" id="currency" name="currency">
                                                <option value="USD" {{ old('currency', 'USD') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                                <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                                <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                                <option value="CAD" {{ old('currency') == 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                                                <option value="AUD" {{ old('currency') == 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="customer_name" class="form-label">Customer Name</label>
                                            <input type="text" class="form-control" id="customer_name" name="customer_name"
                                                value="{{ old('customer_name') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="customer_email" class="form-label">Customer Email</label>
                                            <input type="email" class="form-control" id="customer_email" name="customer_email"
                                                value="{{ old('customer_email') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="customer_address" class="form-label">Customer Address</label>
                                    <textarea class="form-control" id="customer_address" name="customer_address" rows="3">{{ old('customer_address') }}</textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="customer_phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone"
                                                value="{{ old('customer_phone') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="customer_tax_id" class="form-label">Tax ID (Optional)</label>
                                            <input type="text" class="form-control" id="customer_tax_id" name="customer_tax_id"
                                                value="{{ old('customer_tax_id') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Items -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Invoice Items</h5>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                                    <i class="fas fa-plus"></i> Add Item
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="invoice-items">
                                    <!-- Items will be added here -->
                                </div>
                                <div class="text-center py-3" id="no-items-message">
                                    <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No items added yet. Click "Add Item" to get started.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Additional Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes (Optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"
                                        placeholder="Add any additional notes or terms for this invoice...">{{ old('notes') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="terms" class="form-label">Terms and Conditions (Optional)</label>
                                    <textarea class="form-control" id="terms" name="terms" rows="3"
                                        placeholder="Payment terms, late fees, etc...">{{ old('terms') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Invoice Summary -->
                        <div class="card mb-4 sticky-top" style="top: 20px;">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Invoice Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span id="subtotal">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax Rate:</span>
                                    <div class="input-group input-group-sm" style="width: 80px;">
                                        <input type="number" class="form-control form-control-sm" id="tax_rate"
                                            name="tax_rate" value="{{ old('tax_rate', '0') }}" min="0" max="100" step="0.01">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax Amount:</span>
                                    <span id="tax_amount">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Discount:</span>
                                    <div class="input-group input-group-sm" style="width: 100px;">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control form-control-sm" id="discount_amount"
                                            name="discount_amount" value="{{ old('discount_amount', '0') }}" min="0" step="0.01">
                                    </div>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total:</strong>
                                    <strong id="total_amount">$0.00</strong>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="sent" {{ old('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary" name="action" value="save">
                                        <i class="fas fa-save"></i> Save Invoice
                                    </button>
                                    <button type="submit" class="btn btn-success" name="action" value="save_and_send">
                                        <i class="fas fa-paper-plane"></i> Save & Send
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="previewInvoice()">
                                        <i class="fas fa-eye"></i> Preview
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-info btn-sm" onclick="loadTemplate()">
                                        <i class="fas fa-file-alt"></i> Load Template
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="saveTemplate()">
                                        <i class="fas fa-bookmark"></i> Save as Template
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearForm()">
                                        <i class="fas fa-trash"></i> Clear Form
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Item Template (hidden) -->
<div id="item-template" style="display: none;">
    <div class="invoice-item border rounded p-3 mb-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0">Item <span class="item-number"></span></h6>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control item-description" name="items[INDEX][description]" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" class="form-control item-quantity" name="items[INDEX][quantity]"
                        value="1" min="1" step="0.01" required>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Unit Price</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control item-price" name="items[INDEX][unit_price]"
                            value="0.00" min="0" step="0.01" required>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Total</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control item-total" readonly>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-0">
            <label class="form-label">Additional Details (Optional)</label>
            <textarea class="form-control" name="items[INDEX][additional_details]" rows="2"
                placeholder="Additional item details, specifications, etc."></textarea>
        </div>
    </div>
</div>

<script>
let itemIndex = 0;

function addItem() {
    const template = document.getElementById('item-template');
    const newItem = template.cloneNode(true);

    newItem.style.display = 'block';
    newItem.id = '';

    // Update item number and form field names
    const itemNumber = itemIndex + 1;
    newItem.querySelector('.item-number').textContent = itemNumber;

    // Update form field names
    newItem.querySelectorAll('[name]').forEach(field => {
        field.name = field.name.replace('INDEX', itemIndex);
    });

    // Add event listeners for calculations
    const quantityField = newItem.querySelector('.item-quantity');
    const priceField = newItem.querySelector('.item-price');

    quantityField.addEventListener('input', calculateItemTotal);
    priceField.addEventListener('input', calculateItemTotal);

    // Add to items container
    document.getElementById('invoice-items').appendChild(newItem);

    // Hide no items message
    document.getElementById('no-items-message').style.display = 'none';

    // Focus on description field
    newItem.querySelector('.item-description').focus();

    itemIndex++;
    calculateTotals();
}

function removeItem(button) {
    const item = button.closest('.invoice-item');
    item.remove();

    // Show no items message if no items left
    const itemsContainer = document.getElementById('invoice-items');
    if (itemsContainer.children.length === 0) {
        document.getElementById('no-items-message').style.display = 'block';
    }

    calculateTotals();
}

function calculateItemTotal(event) {
    const item = event.target.closest('.invoice-item');
    const quantity = parseFloat(item.querySelector('.item-quantity').value) || 0;
    const price = parseFloat(item.querySelector('.item-price').value) || 0;
    const total = quantity * price;

    item.querySelector('.item-total').value = total.toFixed(2);
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;

    // Calculate subtotal
    document.querySelectorAll('.item-total').forEach(field => {
        subtotal += parseFloat(field.value) || 0;
    });

    // Get tax rate and discount
    const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
    const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;

    // Calculate tax and total
    const taxAmount = (subtotal * taxRate) / 100;
    const total = subtotal + taxAmount - discountAmount;

    // Update display
    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('tax_amount').textContent = '$' + taxAmount.toFixed(2);
    document.getElementById('total_amount').textContent = '$' + total.toFixed(2);
}

function previewInvoice() {
    const form = document.getElementById('invoiceForm');
    const formData = new FormData(form);

    // Open preview in new window
    const previewWindow = window.open('', '_blank', 'width=800,height=600');
    previewWindow.document.write('<p>Loading preview...</p>');

    // In a real implementation, you would send the form data to a preview endpoint
    console.log('Preview functionality to be implemented');
}

function loadTemplate() {
    // Implementation for loading invoice templates
    console.log('Load template functionality to be implemented');
}

function saveTemplate() {
    // Implementation for saving current invoice as template
    console.log('Save template functionality to be implemented');
}

function clearForm() {
    if (confirm('Are you sure you want to clear the form? All data will be lost.')) {
        document.getElementById('invoiceForm').reset();
        document.getElementById('invoice-items').innerHTML = '';
        document.getElementById('no-items-message').style.display = 'block';
        itemIndex = 0;
        calculateTotals();
    }
}

// Event listeners for tax rate and discount changes
document.getElementById('tax_rate').addEventListener('input', calculateTotals);
document.getElementById('discount_amount').addEventListener('input', calculateTotals);

// Add first item automatically
document.addEventListener('DOMContentLoaded', function() {
    addItem();
});
</script>
@endsection
