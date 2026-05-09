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

    private function generatePrivateKey(): string
    {
        do {
            $key = Str::upper(Str::random(16));
        } while (Bill::query()->where('private_key', $key)->exists());

        return $key;
    }

    private function persistInvoicePdf(Bill $bill): void
    {
        $bill->loadMissing(['items', 'user']);
        Storage::disk('public')->makeDirectory(self::PDF_DIRECTORY);

        $oldPath = $bill->path;
        $fileName = Str::lower(Str::ulid()).'.pdf';
        $relativePath = self::PDF_DIRECTORY.'/'.$fileName;

        Pdf::view('invoice', ['bill' => $bill])->disk('public')->save($relativePath);

        $bill->update(['path' => $relativePath]);

        if ($oldPath && $oldPath !== $relativePath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mapBillForList(Bill $bill): array
    {
        $bill->loadMissing(['user']);

        return [
            'id' => $bill->id,
            'private_key' => $bill->private_key,
            'date' => $bill->date,
            'month' => $bill->month,
            'year' => $bill->year,
            'sell_mst' => $bill->sell_mst,
            'customer_name' => $bill->customer_name,
            'path' => $bill->path,
            'pdf_url' => $bill->path ? route('admin.bills.pdf', $bill) : null,
            'created_at' => $bill->created_at?->toDateTimeString(),
            'updated_at' => $bill->updated_at?->toDateTimeString(),
            'user' => [
                'id' => $bill->user?->id,
                'name' => $bill->user?->name,
                'email' => $bill->user?->email,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapBill(Bill $bill): array
    {
        $bill->loadMissing(['items', 'user']);

        return [
            'id' => $bill->id,
            'private_key' => $bill->private_key,
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
            'bill_total_currency' => $bill->bill_total_currency,
            'bill_total_text' => $bill->bill_total_text,
            'path' => $bill->path,
            'pdf_url' => $bill->path ? route('admin.bills.pdf', $bill) : null,
            'created_at' => $bill->created_at?->toDateTimeString(),
            'updated_at' => $bill->updated_at?->toDateTimeString(),
            'user' => [
                'id' => $bill->user?->id,
                'name' => $bill->user?->name,
                'email' => $bill->user?->email,
            ],
            'items' => $bill->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'amount' => $item->amount,
            ])->values(),
        ];
    }

    private function mapBillRowsForItems(array $items): array
    {
        return collect($items)
            ->map(fn (array $row) => [
                'name' => $row['name'] ?? null,
                'unit' => $row['unit'] ?? null,
                'quantity' => $row['quantity'] ?? null,
                'unit_price' => $row['unit_price'] ?? null,
                'amount' => $row['amount'] ?? null,
            ])
            ->all();
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
                        ->where('private_key', 'like', "%{$search}%")
                        ->orWhere('sell_mst', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            });

        if (! $user->isAdmin()) {
            $query->whereBelongsTo($user);
        }

        $bills = $query->paginate($perPage)->withQueryString();

        return Inertia::render('bills/Index', [
            'bills' => $bills->through(fn (Bill $bill) => $this->mapBillForList($bill)),
            'filters' => [
                'search' => $search,
                'perPage' => $perPage,
            ],
            'canManageStaff' => $user->isAdmin(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Bill::class);

        return Inertia::render('bills/Create', [
            'sellMstDefault' => '0301045759',
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
            'private_key' => $this->generatePrivateKey(),
        ]);

        $bill->items()->createMany($this->mapBillRowsForItems($items));

        $this->persistInvoicePdf($bill->fresh(['items', 'user']));

        return to_route('admin.bills.edit', $bill);
    }

    public function pdf(Bill $bill): BinaryFileResponse
    {
        $this->authorize('view', $bill);

        if (! $bill->path || ! Storage::disk('public')->exists($bill->path)) {
            abort(404, 'Khong tim thay file PDF cua hoa don.');
        }

        return response()->file(Storage::disk('public')->path($bill->path));
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
            'sellMstDefault' => '0301045759',
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
        $bill->items()->createMany($this->mapBillRowsForItems($items));

        $this->persistInvoicePdf($bill->fresh(['items', 'user']));

        return redirect()->back();
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        $this->authorize('delete', $bill);

        if ($bill->path && Storage::disk('public')->exists($bill->path)) {
            Storage::disk('public')->delete($bill->path);
        }

        $bill->delete();

        return to_route('admin.bills.index');
    }
}
