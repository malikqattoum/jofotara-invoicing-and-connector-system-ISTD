<?php

namespace Database\Factories;

use App\Models\InvoiceItem;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 10, 500);
        $totalPrice = $quantity * $unitPrice;
        $taxRate = $this->faker->randomFloat(2, 0, 25);
        $taxAmount = $totalPrice * ($taxRate / 100);

        return [
            'invoice_id' => Invoice::factory(),
            'description' => $this->faker->sentence(4),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'product_code' => $this->faker->bothify('PROD-####'),
            'unit_measure' => $this->faker->randomElement(['piece', 'hour', 'kg', 'meter', 'liter']),
            'category' => $this->faker->randomElement(['service', 'product', 'material', 'labor']),
        ];
    }

    public function service()
    {
        return $this->state([
            'category' => 'service',
            'unit_measure' => 'hour',
            'description' => 'Professional service - ' . $this->faker->jobTitle()
        ]);
    }

    public function product()
    {
        return $this->state([
            'category' => 'product',
            'unit_measure' => 'piece',
            'description' => 'Product - ' . $this->faker->word()
        ]);
    }
}
