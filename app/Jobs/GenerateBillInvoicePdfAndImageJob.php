<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Services\BillInvoiceFileGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBillInvoicePdfAndImageJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 2;

    /**
     * Seconds the unique lock is held after the job finishes or fails.
     */
    public int $uniqueFor = 300;

    public function __construct(public readonly int $billId) {}

    public function handle(BillInvoiceFileGenerator $generator): void
    {
        $bill = Bill::query()->with(['items', 'user'])->find($this->billId);

        if ($bill === null) {
            return;
        }

        $generator->regenerate($bill);
    }

    public function uniqueId(): string
    {
        return 'bill-invoice-files-'.$this->billId;
    }
}
