<?php

namespace App\Http\Requests;

use App\Models\Bill;
use Illuminate\Foundation\Http\FormRequest;

class BillUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $bill = $this->route('bill');

        return $bill instanceof Bill && $this->user()?->can('update', $bill) === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'bill_file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }
}
