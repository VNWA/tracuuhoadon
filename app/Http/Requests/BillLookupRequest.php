<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bill_sell_mst' => ['required', 'string', 'max:30'],
            'bill_private_key' => ['required', 'string', 'max:255'],
        ];
    }
}
