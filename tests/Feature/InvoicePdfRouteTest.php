<?php

namespace Tests\Feature;

use App\Models\Bill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\TestCase;

class InvoicePdfRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_bill_pdf_route_returns_a_pdf_response(): void
    {
        Pdf::fake();

        $bill = Bill::factory()->create([
            'sell_mst' => '0301045759-022',
            'private_key' => 'PDFTESTKEY16CHAR',
        ]);

        $bill->items()->create([
            'name' => 'San pham C',
            'unit' => 'Cai',
            'quantity' => '1',
            'unit_price' => '100000',
            'amount' => '100000',
        ]);

        $this->get(route('public-bill.pdf', [
            'bill_sell_mst' => $bill->sell_mst,
            'bill_private_key' => $bill->private_key,
        ]))
            ->assertOk();

        Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf): bool {
            return $pdf->viewName === 'invoice';
        });
    }
}
