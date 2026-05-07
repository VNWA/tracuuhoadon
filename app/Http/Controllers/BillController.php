<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillStoreRequest;
use App\Http\Requests\BillUpdateRequest;
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

    private function generateBillNumber(): string
    {
        do {
            $billNumber = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Bill::query()->where('bill_number', $billNumber)->exists());

        return $billNumber;
    }

    private function generateAndPersistPdf(Bill $bill): void
    {
        $bill->loadMissing(['items', 'user']);
        Storage::disk('public')->makeDirectory(self::PDF_DIRECTORY);

        $oldPath = $bill->bill_path;
        $fileName = Str::lower(Str::ulid()).'.pdf';
        $relativePath = self::PDF_DIRECTORY.'/'.$fileName;

        Pdf::view('invoice', ['bill' => $bill])->disk('public')->save($relativePath);

        $bill->update(['bill_path' => $relativePath]);

        if ($oldPath && $oldPath !== $relativePath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    private function mapBill(Bill $bill): array
    {
        return [
            'id' => $bill->id,
            'bill_symbol' => $bill->bill_symbol,
            'bill_number' => $bill->bill_number,
            'bill_date' => $bill->bill_date,
            'bill_month' => $bill->bill_month,
            'bill_year' => $bill->bill_year,
            'bill_sell_mst' => $bill->bill_sell_mst,
            'bill_private_key' => $bill->bill_private_key,
            'customer_name' => $bill->customer_name,
            'customer_address' => $bill->customer_address,
            'customer_cccd_number' => $bill->customer_cccd_number,
            'customer_phone' => $bill->customer_phone,
            'payment_method' => $bill->payment_method,
            'total_amount' => $bill->total_amount,
            'bill_path' => $bill->bill_path,
            'pdf_url' => $bill->bill_path ? route('admin.bills.pdf', $bill) : null,
            'created_at' => $bill->created_at?->toDateTimeString(),
            'updated_at' => $bill->updated_at?->toDateTimeString(),
            'user' => [
                'id' => $bill->user?->id,
                'name' => $bill->user?->name,
                'email' => $bill->user?->email,
            ],
            'items' => $bill->items->map(fn ($item) => [
                'name' => $item->name,
                'calculation_unit' => $item->calculation_unit,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'amount' => $item->amount,
            ])->values(),
        ];
    }

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $perPage = (int) $request->integer('per_page', 10);
        $search = (string) $request->string('search');

        $query = Bill::query()
            ->with(['user', 'items'])
            ->latest()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('bill_symbol', 'like', "%{$search}%")
                        ->orWhere('bill_number', 'like', "%{$search}%")
                        ->orWhere('bill_sell_mst', 'like', "%{$search}%")
                        ->orWhere('bill_private_key', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
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
            'billSellMstDefault' => '0301045759',
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Bill::class);

        return Inertia::render('bills/Create', [
            'billSellMstDefault' => '0301045759',
        ]);
    }

    public function store(BillStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Bill::class);

        $payload = $request->validated();
        $items = $payload['items'];
        unset($payload['items']);

        $bill = $request->user()->bills()->create([
            ...$payload,
            'bill_symbol' => $this->generateBillSymbol(),
            'bill_number' => $this->generateBillNumber(),
            'bill_sell_mst' => '0301045759',
            'bill_private_key' => Str::upper(Str::random(16)),
        ]);

        $bill->items()->createMany($items);
        $this->generateAndPersistPdf($bill);

        return to_route('admin.bills.index');
    }

    public function pdf(Bill $bill): BinaryFileResponse
    {
        $this->authorize('view', $bill);

        if (! $bill->bill_path || ! Storage::disk('public')->exists($bill->bill_path)) {
            abort(404, 'Khong tim thay file PDF cua hoa don.');
        }

        return response()->file(Storage::disk('public')->path($bill->bill_path));
    }

    public function show(Bill $bill): RedirectResponse
    {
        return to_route('admin.bills.index');
    }

    public function edit(Bill $bill): Response
    {
        $this->authorize('update', $bill);
        $bill->loadMissing(['user', 'items']);

        return Inertia::render('bills/Edit', [
            'bill' => $this->mapBill($bill),
        ]);
    }

    public function update(BillUpdateRequest $request, Bill $bill): RedirectResponse
    {
        $this->authorize('update', $bill);

        $payload = $request->validated();
        $items = $payload['items'];
        unset($payload['items']);

        $bill->update($payload);
        $bill->items()->delete();
        $bill->items()->createMany($items);
        $this->generateAndPersistPdf($bill);

        return to_route('admin.bills.index');
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        $this->authorize('delete', $bill);

        if ($bill->bill_path && Storage::disk('public')->exists($bill->bill_path)) {
            Storage::disk('public')->delete($bill->bill_path);
        }

        $bill->delete();

        return to_route('admin.bills.index');
    }
}
