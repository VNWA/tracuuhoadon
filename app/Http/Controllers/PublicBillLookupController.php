<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillLookupRequest;
use App\Models\Bill;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

class PublicBillLookupController extends Controller
{
    public function search(BillLookupRequest $request): Response
    {
        $lookup = $request->validated();

        $bill = Bill::query()
            ->where('sell_mst', $lookup['bill_sell_mst'])
            ->where('private_key', $lookup['bill_private_key'])
            ->first();

        if (! $bill) {
            return Inertia::render('Home', [
                'lookup' => $lookup,
                'pdfUrl' => null,
                'lookupError' => 'Khong tim thay hoa don voi thong tin da nhap.',
            ]);
        }

        return Inertia::render('Home', [
            'lookup' => $lookup,
            'pdfUrl' => route('public-bill.pdf', $lookup),
            'lookupError' => null,
        ]);
    }

    public function pdf(BillLookupRequest $request)
    {
        $lookup = $request->validated();

        $bill = Bill::query()
            ->with(['items', 'user'])
            ->where('sell_mst', $lookup['bill_sell_mst'])
            ->where('private_key', $lookup['bill_private_key'])
            ->firstOrFail();

        return Pdf::view('invoice', ['bill' => $bill])
            ->name("invoice-{$bill->id}.pdf");
    }
}
