<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
         xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
  <ext:UBLExtensions>
    <ext:UBLExtension>
      <ext:ExtensionContent>
        <cbc:QRCode>{{ $invoice->qr_code ?? '' }}</cbc:QRCode>
      </ext:ExtensionContent>
    </ext:UBLExtension>
    <ext:UBLExtension>
      <ext:ExtensionContent>
        <cbc:Hash>{{ $invoice->hash ?? '' }}</cbc:Hash>
      </ext:ExtensionContent>
    </ext:UBLExtension>
    <ext:UBLExtension>
      <ext:ExtensionContent>
        @if(isset($invoice->digital_signature))
          {!! $invoice->digital_signature !!}
        @endif
      </ext:ExtensionContent>
    </ext:UBLExtension>
  </ext:UBLExtensions>

  <cbc:ID>{{ $invoice->invoice_number }}</cbc:ID>
  <cbc:UUID>{{ $invoice->uuid }}</cbc:UUID>
  <cbc:IssueDate>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') }}</cbc:IssueDate>
  <cbc:InvoiceTypeCode name="{{ $invoice->payment_method ?? 'cash' }}">388</cbc:InvoiceTypeCode>
  <cbc:Note>{{ $invoice->note ?? '' }}</cbc:Note>
  <cbc:DocumentCurrencyCode>{{ $invoice->currency ?? 'JOD' }}</cbc:DocumentCurrencyCode>
  <cbc:TaxCurrencyCode>{{ $invoice->currency ?? 'JOD' }}</cbc:TaxCurrencyCode>
  <cac:AdditionalDocumentReference>
    <cbc:ID>ICV</cbc:ID>
    <cbc:UUID>{{ $invoice->counter ?? 1 }}</cbc:UUID>
  </cac:AdditionalDocumentReference>

  <cac:AccountingSupplierParty>
    <cac:Party>
      <cac:PostalAddress>
        <cac:Country>
          <cbc:IdentificationCode>JO</cbc:IdentificationCode>
        </cac:Country>
      </cac:PostalAddress>
      <cac:PartyTaxScheme>
        <cbc:CompanyID>{{ $seller->tax_number ?? $invoice->tax_number }}</cbc:CompanyID>
        <cac:TaxScheme>
          <cbc:ID>VAT</cbc:ID>
        </cac:TaxScheme>
      </cac:PartyTaxScheme>
      <cac:PartyLegalEntity>
        <cbc:RegistrationName>{{ $seller->name ?? $invoice->organization_name }}</cbc:RegistrationName>
      </cac:PartyLegalEntity>
    </cac:Party>
  </cac:AccountingSupplierParty>

  <cac:AccountingCustomerParty>
    <cac:Party>
      @if($invoice->customer_id && $invoice->customer_id_type)
      <cac:PartyIdentification>
        <cbc:ID schemeID="{{ $invoice->customer_id_type }}">{{ $invoice->customer_id }}</cbc:ID>
      </cac:PartyIdentification>
      @endif
      <cac:PostalAddress>
        <cbc:PostalZone>{{ $invoice->customer_postal_code ?? '' }}</cbc:PostalZone>
        <cac:Country>
          <cbc:IdentificationCode>JO</cbc:IdentificationCode>
        </cac:Country>
      </cac:PostalAddress>
      <cac:PartyTaxScheme>
        <cac:TaxScheme>
          <cbc:ID>VAT</cbc:ID>
        </cac:TaxScheme>
      </cac:PartyTaxScheme>
      <cac:PartyLegalEntity>
        <cbc:RegistrationName>{{ $invoice->customer_name }}</cbc:RegistrationName>
      </cac:PartyLegalEntity>
    </cac:Party>
    <cac:AccountingContact>
      <cbc:Telephone>{{ $invoice->customer_phone ?? '' }}</cbc:Telephone>
    </cac:AccountingContact>
  </cac:AccountingCustomerParty>

  <cac:SellerSupplierParty>
    <cac:Party>
      <cac:PartyIdentification>
        <cbc:ID>{{ $seller->income_source_sequence ?? $invoice->income_source_sequence }}</cbc:ID>
      </cac:PartyIdentification>
    </cac:Party>
  </cac:SellerSupplierParty>

  @if($invoice->discount && $invoice->discount > 0)
  <cac:AllowanceCharge>
    <cbc:ChargeIndicator>false</cbc:ChargeIndicator>
    <cbc:AllowanceChargeReason>discount</cbc:AllowanceChargeReason>
    <cbc:Amount currencyID="{{ $invoice->currency ?? 'JOD' }}">{{ number_format($invoice->discount, 3, '.', '') }}</cbc:Amount>
  </cac:AllowanceCharge>
  @endif

  <cac:LegalMonetaryTotal>
    <cbc:TaxExclusiveAmount currencyID="{{ $invoice->currency ?? 'JOD' }}">{{ number_format($invoice->tax_exclusive_amount, 3, '.', '') }}</cbc:TaxExclusiveAmount>
    <cbc:TaxInclusiveAmount currencyID="{{ $invoice->currency ?? 'JOD' }}">{{ number_format($invoice->tax_inclusive_amount, 3, '.', '') }}</cbc:TaxInclusiveAmount>
    <cbc:AllowanceTotalAmount currencyID="{{ $invoice->currency ?? 'JOD' }}">{{ number_format($invoice->discount, 3, '.', '') }}</cbc:AllowanceTotalAmount>
    <cbc:PayableAmount currencyID="{{ $invoice->currency ?? 'JOD' }}">{{ number_format($invoice->payable_amount, 3, '.', '') }}</cbc:PayableAmount>
  </cac:LegalMonetaryTotal>

  @foreach($invoice->items as $item)
  <cac:InvoiceLine>
    <cbc:ID>{{ $loop->index + 1 }}</cbc:ID>
    <cbc:InvoicedQuantity unitCode="PCE">{{ number_format($item->quantity, 3, '.', '') }}</cbc:InvoicedQuantity>
    <cbc:LineExtensionAmount currencyID="{{ $invoice->currency ?? 'JOD' }}">{{ number_format($item->total, 3, '.', '') }}</cbc:LineExtensionAmount>
    <cac:Item>
      <cbc:Name>{{ $item->item_name }}</cbc:Name>
    </cac:Item>
    <cac:Price>
      <cbc:PriceAmount currencyID="{{ $invoice->currency ?? 'JOD' }}">{{ number_format($item->unit_price, 3, '.', '') }}</cbc:PriceAmount>
      @if($item->discount && $item->discount > 0)
      <cac:AllowanceCharge>
        <cbc:ChargeIndicator>false</cbc:ChargeIndicator>
        <cbc:AllowanceChargeReason>DISCOUNT</cbc:AllowanceChargeReason>
        <cbc:Amount currencyID="{{ $invoice->currency ?? 'JOD' }}">{{ number_format($item->discount, 3, '.', '') }}</cbc:Amount>
      </cac:AllowanceCharge>
      @endif
    </cac:Price>
  </cac:InvoiceLine>
  @endforeach

  <cac:TaxTotal>
    <cbc:TaxAmount currencyID="{{ $invoice->currency ?? 'JOD' }}">{{ number_format($invoice->tax_amount, 3, '.', '') }}</cbc:TaxAmount>
    <cac:TaxSubtotal>
      <cbc:TaxableAmount currencyID="{{ $invoice->currency ?? 'JOD' }}">{{ number_format($invoice->taxable_amount, 3, '.', '') }}</cbc:TaxableAmount>
      <cbc:TaxAmount currencyID="{{ $invoice->currency ?? 'JOD' }}">{{ number_format($invoice->tax_amount, 3, '.', '') }}</cbc:TaxAmount>
      <cac:TaxCategory>
        <cac:TaxScheme>
          <cbc:ID>VAT</cbc:ID>
          <cbc:Name>Sales Tax</cbc:Name>
        </cac:TaxScheme>
      </cac:TaxCategory>
    </cac:TaxSubtotal>
  </cac:TaxTotal>

</Invoice>
