<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bill_date' => ['nullable', 'string', 'max:2'],
            'bill_month' => ['nullable', 'string', 'max:2'],
            'bill_year' => ['nullable', 'string', 'max:4'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_address' => ['nullable', 'string', 'max:255'],
            'customer_cccd_number' => ['nullable', 'string', 'max:30'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['required', 'string', 'max:100'],
            'total_amount' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.calculation_unit' => ['required', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'string', 'max:100'],
            'items.*.amount' => ['required', 'string', 'max:100'],
        ];
    }
}
