<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BillUpdateItemsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function billPayload(Bill $bill): array
    {
        return [
            'date' => $bill->date,
            'month' => $bill->month,
            'year' => $bill->year,
            'sell_mst' => $bill->sell_mst,
            'customer_name' => $bill->customer_name,
            'unit_name' => $bill->unit_name,
            'customer_mst' => $bill->customer_mst,
            'customer_address' => $bill->customer_address,
            'customer_cccd' => $bill->customer_cccd,
            'customer_phone' => $bill->customer_phone,
            'payment_method' => $bill->payment_method,
            'note' => $bill->note,
            'bill_total_currency' => '2.500.000',
            'bill_total_text' => $bill->bill_total_text,
        ];
    }

    public function test_update_persists_two_item_rows_and_regenerates_pdf(): void
    {
        Pdf::fake();
        Role::findOrCreate('staff');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $bill = Bill::factory()->for($staff)->create([
            'pdf_path' => 'files/legacy.pdf',
            'image_path' => 'files/images/legacy.jpg',
        ]);

        BillItem::factory()->count(3)->for($bill)->create();

        $payload = $this->billPayload($bill);
        $payload['items'] = [
            [
                'name' => 'Hang A',
                'unit' => 'Cai',
                'quantity' => 2,
                'unit_price' => '1.000.000',
                'amount' => '2.000.000',
            ],
            [
                'name' => 'Hang B',
                'unit' => 'Bo',
                'quantity' => 1,
                'unit_price' => '500.000',
                'amount' => '500.000',
            ],
        ];

        $editUrl = route('admin.bills.edit', $bill);

        $this->actingAs($staff)->from($editUrl)->put(route('admin.bills.update', $bill), $payload)
            ->assertRedirect($editUrl);

        $bill->refresh();

        $this->assertCount(2, $bill->items);
        $this->assertSame('Hang A', $bill->items[0]->name);
        $this->assertSame('2', $bill->items[0]->quantity);

        Pdf::assertSaved(function (PdfBuilder $pdf, string $path) use ($bill): bool {
            return str_contains($path, (string) $bill->pdf_path);
        });
    }
}
