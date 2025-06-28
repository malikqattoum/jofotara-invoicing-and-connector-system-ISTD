<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $invoiceDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $dueDate = (clone $invoiceDate)->modify('+30 days');

        $netAmount = $this->faker->randomFloat(2, 100, 5000);
        $taxRate = 0.16; // Jordan tax rate
        $taxAmount = $netAmount * $taxRate;
        $totalAmount = $netAmount + $taxAmount;

        $statuses = ['draft', 'submitted', 'rejected'];
        $paymentStatuses = ['pending', 'paid', 'overdue'];
        $status = $this->faker->randomElement($statuses);

        // Logic for payment status based on invoice status and due date
        $paymentStatus = 'pending';
        $paidAt = null;

        if ($status === 'submitted') {
            if ($dueDate < now() && $this->faker->boolean(70)) {
                $paymentStatus = 'paid';
                $paidAt = $this->faker->dateTimeBetween($invoiceDate, 'now');
            } elseif ($dueDate < now()) {
                $paymentStatus = 'overdue';
            } else {
                $paymentStatus = $this->faker->randomElement(['pending', 'paid']);
                if ($paymentStatus === 'paid') {
                    $paidAt = $this->faker->dateTimeBetween($invoiceDate, 'now');
                }
            }
        }

        return [
            'organization_id' => 1,
            'vendor_id' => 1,
            'invoice_number' => 'INV-' . $this->faker->unique()->numberBetween(1000, 99999),
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'customer_name' => $this->faker->company(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_address' => $this->faker->address(),
            'customer_tax_number' => $this->faker->numerify('TAX###########'),
            'total_amount' => $totalAmount,
            'net_amount' => $netAmount,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'paid_at' => $paidAt,
            'payment_method' => $paidAt ? $this->faker->randomElement(['bank_transfer', 'credit_card', 'cash']) : null,
            'payment_reference' => $paidAt ? $this->faker->uuid() : null,
            'uuid' => Str::uuid(),
            'currency' => 'JOD',
            'integration_type' => $this->faker->randomElement(['zatca', 'e_invoice', 'manual']),
            'submitted_at' => $status === 'submitted' ? $invoiceDate : null,
            'processed_at' => in_array($status, ['submitted', 'rejected']) ?
                (clone $invoiceDate)->modify('+' . $this->faker->numberBetween(1, 48) . ' hours') : null,
            'rejection_reason' => $status === 'rejected' ? $this->faker->sentence() : null,
            'revision_number' => $status === 'rejected' ? $this->faker->numberBetween(1, 3) : 1,
            'compliance_status' => $status === 'submitted' ? 'approved' :
                ($status === 'rejected' ? 'rejected' : 'pending'),
            'line_items_summary' => [
                'item_count' => $this->faker->numberBetween(1, 5),
                'categories' => [$this->faker->word(), $this->faker->word()],
            ],
            'invoice_type' => $this->faker->randomElement(['standard', 'credit_note', 'debit_note']),
            'audit_trail' => [
                [
                    'action' => 'created',
                    'timestamp' => $invoiceDate->format('Y-m-d H:i:s'),
                    'user' => $this->faker->name(),
                ]
            ],
            'created_by' => $this->faker->name(),
            'updated_by' => $this->faker->name(),
        ];
    }

    /**
     * Create a submitted invoice
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'compliance_status' => 'approved',
        ]);
    }

    /**
     * Create a paid invoice
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $paidAt = $this->faker->dateTimeBetween($attributes['invoice_date'], 'now');

            return [
                'status' => 'submitted',
                'payment_status' => 'paid',
                'paid_at' => $paidAt,
                'payment_method' => $this->faker->randomElement(['bank_transfer', 'credit_card', 'cash']),
                'payment_reference' => $this->faker->uuid(),
                'compliance_status' => 'approved',
            ];
        });
    }

    /**
     * Create an overdue invoice
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $dueDate = $this->faker->dateTimeBetween('-60 days', '-1 day');

            return [
                'status' => 'submitted',
                'payment_status' => 'overdue',
                'due_date' => $dueDate,
                'paid_at' => null,
                'compliance_status' => 'approved',
            ];
        });
    }

    /**
     * Create a rejected invoice
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'payment_status' => 'pending',
            'rejection_reason' => $this->faker->sentence(),
            'compliance_status' => 'rejected',
            'paid_at' => null,
        ]);
    }

    /**
     * Create a high-value invoice
     */
    public function highValue(): static
    {
        return $this->state(function (array $attributes) {
            $netAmount = $this->faker->randomFloat(2, 10000, 50000);
            $taxAmount = $netAmount * 0.16;

            return [
                'net_amount' => $netAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $netAmount + $taxAmount,
            ];
        });
    }

    /**
     * Create invoices for a specific customer
     */
    public function forCustomer(string $customerName, string $taxNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_name' => $customerName,
            'customer_tax_number' => $taxNumber,
        ]);
    }
}
