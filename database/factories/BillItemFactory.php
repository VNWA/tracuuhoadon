<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\BillItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillItem>
 */
class BillItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = (string) fake()->numberBetween(1, 10);
        $unitPrice = (string) fake()->numberBetween(100000, 5000000);

        return [
            'bill_id' => Bill::factory(),
            'name' => fake()->words(3, true),
            'unit' => fake()->randomElement(['Cai', 'Bo', 'Chiec']),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => (string) ((int) $quantity * (int) $unitPrice),
        ];
    }
}
