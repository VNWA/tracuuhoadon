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
            'bill_symbol' => Str::upper(fake()->unique()->bothify('#?##???')),
            'bill_private_key' => Str::upper(Str::random(16)),
            'bill_date' => str_pad((string) fake()->numberBetween(1, 28), 2, '0', STR_PAD_LEFT),
            'bill_month' => str_pad((string) fake()->numberBetween(1, 12), 2, '0', STR_PAD_LEFT),
            'bill_year' => (string) fake()->numberBetween(2020, 2030),
            'bill_sell_mst' => '0301045759-022',
            'bill_demo_path' => null,
            'bill_path' => null,
        ];
    }
}
