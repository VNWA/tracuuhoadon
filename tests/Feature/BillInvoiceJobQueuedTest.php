<?php

namespace Tests\Feature;

use App\Jobs\GenerateBillInvoicePdfAndImageJob;
use App\Models\Bill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BillInvoiceJobQueuedTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_pushes_generate_bill_invoice_job(): void
    {
        Queue::fake();
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
            'items' => [
                [
                    'name' => 'San pham 1',
                    'unit' => 'Cai',
                    'quantity' => '1',
                    'unit_price' => '100000',
                    'amount' => '100000',
                ],
            ],
        ];

        $response = $this->actingAs($staff)->post(route('admin.bills.store'), $payload);

        $bill = Bill::query()->firstOrFail();
        $response->assertRedirect(route('admin.bills.edit', $bill));

        Queue::assertPushed(GenerateBillInvoicePdfAndImageJob::class, function (GenerateBillInvoicePdfAndImageJob $job) use ($bill): bool {
            return $job->billId === $bill->id;
        });
    }
}
