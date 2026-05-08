<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillUploadRequest;
use App\Models\Bill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillController extends Controller
{
    private const PDF_DIRECTORY = 'files';

    private function generateBillSymbol(): string
    {
        do {
            $symbol = random_int(1, 9)
                .Str::upper(Str::random(1))
                .str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT)
                .Str::upper(Str::random(3));
        } while (Bill::query()->where('bill_symbol', $symbol)->exists());

        return $symbol;
    }

    private function persistDemoPdf(Bill $bill): string
    {
        $bill->loadMissing(['user']);
        Storage::disk('public')->makeDirectory(self::PDF_DIRECTORY);

        $fileName = 'demo-'.Str::lower(Str::ulid()).'.pdf';
        $relativePath = self::PDF_DIRECTORY.'/'.$fileName;

        Pdf::view('invoice', ['bill' => $bill])->disk('public')->save($relativePath);

        return $relativePath;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapBill(Bill $bill): array
    {
        return [
            'id' => $bill->id,
            'bill_symbol' => $bill->bill_symbol,
            'bill_date' => $bill->bill_date,
            'bill_month' => $bill->bill_month,
            'bill_year' => $bill->bill_year,
            'bill_sell_mst' => $bill->bill_sell_mst,
            'bill_private_key' => $bill->bill_private_key,
            'bill_path' => $bill->bill_path,
            'bill_demo_path' => $bill->bill_demo_path,
            'demo_download_url' => $bill->bill_demo_path ? route('admin.bills.demo', $bill) : null,
            'pdf_url' => $bill->bill_path ? route('admin.bills.pdf', $bill) : null,
            'created_at' => $bill->created_at?->toDateTimeString(),
            'updated_at' => $bill->updated_at?->toDateTimeString(),
            'user' => [
                'id' => $bill->user?->id,
                'name' => $bill->user?->name,
                'email' => $bill->user?->email,
            ],
        ];
    }

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $perPage = (int) $request->integer('per_page', 10);
        $search = (string) $request->string('search');

        $query = Bill::query()
            ->with(['user'])
            ->latest()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('bill_symbol', 'like', "%{$search}%")
                        ->orWhere('bill_sell_mst', 'like', "%{$search}%")
                        ->orWhere('bill_private_key', 'like', "%{$search}%");
                });
            });

        if (! $user->isAdmin()) {
            $query->whereBelongsTo($user);
        }

        $bills = $query->paginate($perPage)->withQueryString();

        return Inertia::render('bills/Index', [
            'bills' => $bills->through(fn (Bill $bill) => $this->mapBill($bill)),
            'filters' => [
                'search' => $search,
                'perPage' => $perPage,
            ],
            'canManageStaff' => $user->isAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Bill::class);

        $now = now();

        $bill = $request->user()->bills()->create([
            'bill_symbol' => $this->generateBillSymbol(),
            'bill_private_key' => Str::upper(Str::random(16)),
            'bill_date' => $now->format('d'),
            'bill_month' => $now->format('m'),
            'bill_year' => $now->format('Y'),
        ]);

        $demoPath = $this->persistDemoPdf($bill->fresh());
        $bill->update(['bill_demo_path' => $demoPath]);

        return to_route('admin.bills.edit', $bill);
    }

    public function pdf(Bill $bill): BinaryFileResponse
    {
        $this->authorize('view', $bill);

        if (! $bill->bill_path || ! Storage::disk('public')->exists($bill->bill_path)) {
            abort(404, 'Khong tim thay file PDF cua hoa don.');
        }

        return response()->file(Storage::disk('public')->path($bill->bill_path));
    }

    public function demo(Bill $bill): BinaryFileResponse
    {
        $this->authorize('view', $bill);

        if (! $bill->bill_demo_path || ! Storage::disk('public')->exists($bill->bill_demo_path)) {
            abort(404, 'Khong tim thay file ban mau.');
        }

        return response()->download(
            Storage::disk('public')->path($bill->bill_demo_path),
            'ban-mau-hoa-don-'.$bill->bill_private_key.'.pdf'
        );
    }

    public function show(Bill $bill): RedirectResponse
    {
        return to_route('admin.bills.index');
    }

    public function edit(Bill $bill): Response
    {
        $this->authorize('update', $bill);
        $bill->loadMissing(['user']);

        return Inertia::render('bills/Edit', [
            'bill' => $this->mapBill($bill),
        ]);
    }

    public function upload(BillUploadRequest $request, Bill $bill): RedirectResponse
    {
        $this->authorize('update', $bill);

        Storage::disk('public')->makeDirectory(self::PDF_DIRECTORY);

        $path = $request->file('bill_file')->store(self::PDF_DIRECTORY, 'public');
        $oldPath = $bill->bill_path;
        $bill->update(['bill_path' => $path]);

        if ($oldPath && $oldPath !== $path && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->back();
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        $this->authorize('delete', $bill);

        foreach (['bill_path', 'bill_demo_path'] as $column) {
            $path = $bill->{$column};
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $bill->delete();

        return to_route('admin.bills.index');
    }
}
