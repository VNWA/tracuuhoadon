<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BillPdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_demo_pdf_and_redirects_to_edit_when_storing_a_bill(): void
    {
        Pdf::fake();
        $this->travelTo(Carbon::parse('2026-05-08 14:30:00'));
        Role::findOrCreate('staff');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $response = $this->actingAs($staff)->post(route('admin.bills.store'));

        $bill = Bill::query()->firstOrFail();
        $response->assertRedirect(route('admin.bills.edit', $bill));

        $this->assertNotNull($bill->bill_demo_path);
        $this->assertStringStartsWith('files/demo-', (string) $bill->bill_demo_path);
        $this->assertStringEndsWith('.pdf', (string) $bill->bill_demo_path);

        $this->assertSame('08', $bill->bill_date);
        $this->assertSame('05', $bill->bill_month);
        $this->assertSame('2026', $bill->bill_year);

        Pdf::assertSaved(function (PdfBuilder $pdf, string $path) use ($bill): bool {
            return str_contains($path, (string) $bill->bill_demo_path);
        });
    }

    public function test_it_stores_uploaded_pdf_to_bill_path(): void
    {
        Storage::fake('public');
        Role::findOrCreate('staff');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $bill = Bill::factory()->for($staff)->create([
            'bill_demo_path' => 'files/demo-test.pdf',
        ]);

        Storage::disk('public')->put('files/demo-test.pdf', 'fake demo');

        $file = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');

        $response = $this->actingAs($staff)->post(route('admin.bills.upload', $bill), [
            'bill_file' => $file,
        ]);

        $response->assertRedirect();
        $bill->refresh();
        $this->assertNotNull($bill->bill_path);
        Storage::disk('public')->assertExists($bill->bill_path);
    }
}
