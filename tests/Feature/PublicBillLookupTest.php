<?php

namespace Tests\Feature;

use App\Models\Bill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\TestCase;

class PublicBillLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_lookup_returns_pdf_url_when_bill_exists(): void
    {
        $bill = Bill::factory()->create([
            'bill_sell_mst' => '0301045759',
            'bill_private_key' => 'SECRETKEY123',
        ]);
        $bill->items()->create([
            'name' => 'San pham A',
            'calculation_unit' => 'Cai',
            'quantity' => '1',
            'unit_price' => '100000',
            'amount' => '100000',
        ]);

        $response = $this->post(route('public-bill.search'), [
            'bill_sell_mst' => $bill->bill_sell_mst,
            'bill_private_key' => $bill->bill_private_key,
        ]);

        $response->assertOk();
        $response->assertSee('/invoice-pdf');
    }

    public function test_public_pdf_route_responds_with_invoice_pdf(): void
    {
        Pdf::fake();

        $bill = Bill::factory()->create([
            'bill_sell_mst' => '0301045759',
            'bill_private_key' => 'SECRETKEY123',
        ]);

        $bill->items()->create([
            'name' => 'San pham B',
            'calculation_unit' => 'Cai',
            'quantity' => '2',
            'unit_price' => '50000',
            'amount' => '100000',
        ]);

        $response = $this->get(route('public-bill.pdf', [
            'bill_sell_mst' => $bill->bill_sell_mst,
            'bill_private_key' => $bill->bill_private_key,
        ]));

        $response->assertOk();
        Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf): bool {
            return $pdf->viewName === 'invoice';
        });
    }
}
