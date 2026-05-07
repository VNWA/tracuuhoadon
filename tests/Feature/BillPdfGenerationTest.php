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

    public function test_it_generates_and_saves_pdf_when_creating_bill(): void
    {
        Pdf::fake();
        Role::findOrCreate('staff');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $response = $this->actingAs($staff)->post(route('admin.bills.store'), [
            'customer_name' => 'Nguyen Van A',
            'customer_address' => 'HCM',
            'customer_cccd_number' => '012345678901',
            'customer_phone' => '0900000000',
            'payment_method' => 'Tien mat',
            'total_amount' => '100000',
            'items' => [
                [
                    'name' => 'San pham 1',
                    'calculation_unit' => 'Cai',
                    'quantity' => '1',
                    'unit_price' => '100000',
                    'amount' => '100000',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.bills.index'));

        $bill = Bill::query()->firstOrFail();
        $this->assertNotNull($bill->bill_path);
        $this->assertStringStartsWith('files/', (string) $bill->bill_path);
        $this->assertStringEndsWith('.pdf', (string) $bill->bill_path);

        Pdf::assertSaved(function (PdfBuilder $pdf, string $path) use ($bill): bool {
            return str_contains($path, (string) $bill->bill_path);
        });
    }

    public function test_it_regenerates_pdf_when_updating_bill(): void
    {
        Pdf::fake();
        Role::findOrCreate('staff');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $bill = Bill::factory()->for($staff)->create([
            'bill_path' => 'files/old-file.pdf',
        ]);
        $bill->items()->create([
            'name' => 'San pham cu',
            'calculation_unit' => 'Cai',
            'quantity' => '1',
            'unit_price' => '100000',
            'amount' => '100000',
        ]);

        $response = $this->actingAs($staff)->put(route('admin.bills.update', $bill), [
            'customer_name' => 'Nguyen Van B',
            'customer_address' => 'HCM',
            'customer_cccd_number' => '012345678901',
            'customer_phone' => '0900000001',
            'payment_method' => 'Chuyen khoan',
            'total_amount' => '200000',
            'items' => [
                [
                    'name' => 'San pham moi',
                    'calculation_unit' => 'Cai',
                    'quantity' => '2',
                    'unit_price' => '100000',
                    'amount' => '200000',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.bills.index'));

        $bill->refresh();
        $this->assertNotSame('files/old-file.pdf', $bill->bill_path);
        $this->assertStringStartsWith('files/', (string) $bill->bill_path);

        Pdf::assertSaved(function (PdfBuilder $pdf, string $path) use ($bill): bool {
            return str_contains($path, (string) $bill->bill_path);
        });
    }
}
