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
            'sell_mst' => '0301045759-022',
            'private_key' => 'SECRETKEY123456',
        ]);

        $response = $this->post(route('public-bill.search'), [
            'bill_sell_mst' => $bill->sell_mst,
            'bill_private_key' => $bill->private_key,
        ]);

        $response->assertOk();
        $response->assertSee('/invoice-pdf');
    }

    public function test_public_pdf_route_responds_with_invoice_pdf(): void
    {
        Pdf::fake();

        $bill = Bill::factory()->create([
            'sell_mst' => '0301045759-022',
            'private_key' => 'SECRETKEY123456',
        ]);

        $response = $this->get(route('public-bill.pdf', [
            'bill_sell_mst' => $bill->sell_mst,
            'bill_private_key' => $bill->private_key,
        ]));

        $response->assertOk();
        Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf): bool {
            return $pdf->viewName === 'invoice';
        });
    }
}
