<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'string', 'max:4'],
            'month' => ['nullable', 'string', 'max:4'],
            'year' => ['nullable', 'string', 'max:8'],
            'sell_mst' => ['nullable', 'string', 'max:40'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'unit_name' => ['nullable', 'string', 'max:255'],
            'customer_mst' => ['nullable', 'string', 'max:30'],
            'customer_address' => ['nullable', 'string', 'max:500'],
            'customer_cccd' => ['nullable', 'string', 'max:30'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            'bill_total_currency' => ['nullable', 'string', 'max:100'],
            'bill_total_text' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'size:5'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'string', 'max:100'],
            'items.*.amount' => ['nullable', 'string', 'max:100'],
        ];
    }
}
