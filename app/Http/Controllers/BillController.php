<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillStoreRequest;
use App\Http\Requests\BillUpdateRequest;
use App\Jobs\GenerateBillInvoicePdfAndImageJob;
use App\Models\Bill;
use App\Services\BillInvoiceFileGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillController extends Controller
{
    public function __construct(
        private readonly BillInvoiceFileGenerator $billInvoiceFileGenerator,
    ) {}

    private function withInvoiceQueuedFlashIfAsync(RedirectResponse $response): RedirectResponse
    {
        if (config('queue.default') === 'sync') {
            return $response;
        }

        return $response->with('invoice_generation_queued', true);
    }

    private function generatePrivateKey(): string
    {
        do {
            $key = Str::upper(Str::random(16));
        } while (Bill::query()->where('private_key', $key)->exists());

        return $key;
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
            'pdf_path' => $bill->pdf_path,
            'image_path' => $bill->image_path,
            'pdf_url' => $bill->pdf_path ? Storage::disk('public')->url($bill->pdf_path) : null,
            'jpg_url' => $bill->image_path ? Storage::disk('public')->url($bill->image_path) : null,
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
            'pdf_path' => $bill->pdf_path,
            'image_path' => $bill->image_path,
            'pdf_url' => $bill->pdf_path ? Storage::disk('public')->url($bill->pdf_path) : null,
            'jpg_url' => $bill->image_path ? Storage::disk('public')->url($bill->image_path) : null,
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

    /**
     * @return array<int, array<string, mixed>>
     */
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

        GenerateBillInvoicePdfAndImageJob::dispatch($bill->id);

        return $this->withInvoiceQueuedFlashIfAsync(to_route('admin.bills.edit', $bill));
    }

    public function pdf(Bill $bill): BinaryFileResponse
    {
        $this->authorize('view', $bill);

        if (! $bill->pdf_path || ! Storage::disk('public')->exists($bill->pdf_path)) {
            abort(404, 'Khong tim thay file PDF cua hoa don.');
        }

        return response()->file(Storage::disk('public')->path($bill->pdf_path));
    }

    public function image(Bill $bill): BinaryFileResponse
    {
        $this->authorize('view', $bill);

        $imagePath = $this->billInvoiceFileGenerator->ensureJpegPreview($bill);

        if (! Storage::disk('public')->exists($imagePath)) {
            abort(404, 'Khong the tao file JPG cua hoa don.');
        }

        return response()->file(Storage::disk('public')->path($imagePath), [
            'Content-Type' => 'image/jpeg',
        ]);
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

        GenerateBillInvoicePdfAndImageJob::dispatch($bill->id);

        return $this->withInvoiceQueuedFlashIfAsync(redirect()->back());
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        $this->authorize('delete', $bill);

        if ($bill->pdf_path && Storage::disk('public')->exists($bill->pdf_path)) {
            Storage::disk('public')->delete($bill->pdf_path);
        }

        if ($bill->image_path && Storage::disk('public')->exists($bill->image_path)) {
            Storage::disk('public')->delete($bill->image_path);
        }

        $bill->delete();

        return to_route('admin.bills.index');
    }
}
