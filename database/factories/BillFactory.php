<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'private_key' => Str::upper(Str::random(16)),
            'date' => str_pad((string) fake()->numberBetween(1, 28), 2, '0', STR_PAD_LEFT),
            'month' => str_pad((string) fake()->numberBetween(1, 12), 2, '0', STR_PAD_LEFT),
            'year' => (string) fake()->numberBetween(2020, 2030),
            'sell_mst' => '0301045759-022',
            'customer_name' => fake()->name(),
            'unit_name' => null,
            'customer_mst' => null,
            'customer_address' => fake()->address(),
            'customer_cccd' => null,
            'customer_phone' => fake()->phoneNumber(),
            'payment_method' => fake()->randomElement(['Tien mat', 'Chuyen khoan']),
            'note' => null,
            'bill_total_currency' => null,
            'bill_total_text' => null,
            'pdf_path' => null,
            'image_path' => null,
        ];
    }
}
