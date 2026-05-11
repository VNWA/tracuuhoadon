<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        /** @var mixed $rawItems */
        $rawItems = $this->input('items');

        if (! is_array($rawItems)) {
            return;
        }

        $items = [];

        foreach ($rawItems as $item) {
            if (! is_array($item)) {
                $items[] = $item;

                continue;
            }

            if (array_key_exists('quantity', $item) && $item['quantity'] !== null && $item['quantity'] !== '' && is_numeric($item['quantity'])) {
                $item['quantity'] = (string) $item['quantity'];
            }

            $items[] = $item;
        }

        $this->merge(['items' => $items]);
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
            'items' => ['required', 'array', 'min:1', 'max:5'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'string', 'max:100'],
            'items.*.amount' => ['nullable', 'string', 'max:100'],
        ];
    }
}
