<?php

namespace App\Services;

use App\Models\Bill;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\Process\Process;

class BillInvoiceFileGenerator
{
    private const string PDF_DIRECTORY = 'files';

    private const string JPEG_DIRECTORY = 'files/images';

    private function generateUniqueFileName(string $extension): string
    {
        return now()->format('YmdHisv').'-'.Str::lower(Str::ulid()).'.'.$extension;
    }

    /**
     * Render invoice PDF and JPEG preview; replaces paths on the bill and deletes previous files.
     */
    public function regenerate(Bill $bill): void
    {
        $bill->loadMissing(['items', 'user']);
        Storage::disk('public')->makeDirectory(self::PDF_DIRECTORY);
        Storage::disk('public')->makeDirectory(self::JPEG_DIRECTORY);

        $oldPdfPath = $bill->pdf_path;
        $oldImagePath = $bill->image_path;
        $relativePath = self::PDF_DIRECTORY.'/'.$this->generateUniqueFileName('pdf');

        Pdf::view('invoice', ['bill' => $bill])->disk('public')->save($relativePath);

        $bill->update([
            'pdf_path' => $relativePath,
        ]);

        if ($oldPdfPath && $oldPdfPath !== $relativePath && Storage::disk('public')->exists($oldPdfPath)) {
            Storage::disk('public')->delete($oldPdfPath);
        }

        $imagePath = $this->generateInvoiceJpeg($bill->fresh(['items', 'user']), true);
        if ($bill->image_path !== $imagePath) {
            $bill->update(['image_path' => $imagePath]);
        }

        if ($oldImagePath && $oldImagePath !== $imagePath && Storage::disk('public')->exists($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }
    }

    /**
     * Ensure a JPEG exists for on-demand HTTP responses (does not force regeneration when file exists).
     */
    public function ensureJpegPreview(Bill $bill): string
    {
        $imagePath = $this->generateInvoiceJpeg($bill, false);
        if ($bill->image_path !== $imagePath) {
            $bill->update(['image_path' => $imagePath]);
        }

        return $imagePath;
    }

    private function generateInvoiceJpeg(Bill $bill, bool $forceRegenerate = false): string
    {
        $bill->loadMissing(['items', 'user']);
        Storage::disk('public')->makeDirectory(self::JPEG_DIRECTORY);

        $relativePath = $bill->image_path ?: self::JPEG_DIRECTORY.'/'.$this->generateUniqueFileName('jpg');
        if ($forceRegenerate) {
            $relativePath = self::JPEG_DIRECTORY.'/'.$this->generateUniqueFileName('jpg');
        }
        $absolutePath = Storage::disk('public')->path($relativePath);

        if (! $forceRegenerate && Storage::disk('public')->exists($relativePath)) {
            return $relativePath;
        }

        try {
            $this->convertPdfToJpeg($bill, $absolutePath);
        } catch (\Throwable $exception) {
            try {
                Browsershot::html(view('invoice', ['bill' => $bill])->render())
                    ->setScreenshotType('jpeg', 95)
                    ->windowSize(1240, 1754)
                    ->save($absolutePath);
            } catch (\Throwable $fallbackException) {
                Log::warning('Cannot generate bill JPEG preview.', [
                    'bill_id' => $bill->id,
                    'message' => $fallbackException->getMessage(),
                    'initial_message' => $exception->getMessage(),
                ]);
            }
        }

        return $relativePath;
    }

    private function convertPdfToJpeg(Bill $bill, string $targetPath): void
    {
        if (! $bill->pdf_path || ! Storage::disk('public')->exists($bill->pdf_path)) {
            throw new \RuntimeException('Bill PDF does not exist to convert.');
        }

        $pdfPath = Storage::disk('public')->path($bill->pdf_path);
        $targetBasePath = substr($targetPath, 0, -4);

        $process = new Process([
            'pdftoppm',
            '-jpeg',
            '-singlefile',
            '-f',
            '1',
            '-r',
            '180',
            $pdfPath,
            $targetBasePath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful() || ! file_exists($targetPath)) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'pdftoppm conversion failed.');
        }
    }
}
