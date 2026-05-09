<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BillPdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array<string, string>>
     */
    private function fiveItems(): array
    {
        return [
            [
                'name' => 'San pham 1',
                'unit' => 'Cai',
                'quantity' => '1',
                'unit_price' => '100000',
                'amount' => '100000',
            ],
            [
                'name' => '',
                'unit' => '',
                'quantity' => '',
                'unit_price' => '',
                'amount' => '',
            ],
            [
                'name' => '',
                'unit' => '',
                'quantity' => '',
                'unit_price' => '',
                'amount' => '',
            ],
            [
                'name' => '',
                'unit' => '',
                'quantity' => '',
                'unit_price' => '',
                'amount' => '',
            ],
            [
                'name' => '',
                'unit' => '',
                'quantity' => '',
                'unit_price' => '',
                'amount' => '',
            ],
        ];
    }

    public function test_it_stores_bill_generates_pdf_and_redirects_to_edit(): void
    {
        Pdf::fake();
        Role::findOrCreate('staff');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $payload = [
            'date' => '08',
            'month' => '05',
            'year' => '2026',
            'sell_mst' => '0301045759-022',
            'customer_name' => 'Nguyen Van A',
            'unit_name' => '',
            'customer_mst' => '',
            'customer_address' => '',
            'customer_cccd' => '',
            'customer_phone' => '',
            'payment_method' => 'Chuyen khoan',
            'note' => '',
            'bill_total_currency' => '100000',
            'bill_total_text' => 'Mot tram nghin dong',
            'items' => $this->fiveItems(),
        ];

        $response = $this->actingAs($staff)->post(route('admin.bills.store'), $payload);

        $bill = Bill::query()->firstOrFail();
        $response->assertRedirect(route('admin.bills.edit', $bill));

        $this->assertNotEmpty($bill->private_key);
        $this->assertNotNull($bill->pdf_path);
        $this->assertStringStartsWith('files/', (string) $bill->pdf_path);
        $this->assertStringEndsWith('.pdf', (string) $bill->pdf_path);
        $this->assertNotNull($bill->image_path);
        $this->assertStringStartsWith('files/images/', (string) $bill->image_path);
        $this->assertStringEndsWith('.jpg', (string) $bill->image_path);
        $this->assertCount(5, $bill->items);

        Pdf::assertSaved(function (PdfBuilder $pdf, string $path) use ($bill): bool {
            return str_contains($path, (string) $bill->pdf_path);
        });
    }
}
