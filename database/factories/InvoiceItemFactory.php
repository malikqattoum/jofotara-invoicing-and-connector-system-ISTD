<?php

namespace Database\Factories;

use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition()
    {
        return [
            'invoice_id' => $this->faker->randomNumber(),
            'item_name' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->randomFloat(2, 1, 100),
            'tax' => $this->faker->randomFloat(2, 0, 20),
            'total' => function (array $attributes) {
                return $attributes['quantity'] * $attributes['unit_price'] + $attributes['tax'];
            },
        ];
    }
}
