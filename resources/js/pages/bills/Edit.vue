<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { index as billsIndex, update as updateBillRoute } from '@/routes/admin/bills';
import { watch } from 'vue';

type ItemRow = {
    name: string;
    unit: string;
    quantity: string;
    unit_price: string;
    amount: string;
};

type Bill = {
    id: number;
    private_key: string;
    date: string | null;
    month: string | null;
    year: string | null;
    sell_mst: string;
    customer_name: string | null;
    unit_name: string | null;
    customer_mst: string | null;
    customer_address: string | null;
    customer_cccd: string | null;
    customer_phone: string | null;
    payment_method: string | null;
    note: string | null;
    bill_total_currency: string | null;
    bill_total_text: string | null;
    pdf_url: string | null;
    jpg_url: string | null;
    items: Array<{
        id?: number;
        name: string | null;
        unit: string | null;
        quantity: string | null;
        unit_price: string | null;
        amount: string | null;
    }>;
};

const props = defineProps<{
    bill: Bill;
    sellMstDefault: string;
}>();

defineOptions({ layout: AppLayout });

const emptyRow = (): ItemRow => ({
    name: '',
    unit: '',
    quantity: '',
    unit_price: '',
    amount: '',
});

const normalizeItems = (items: Bill['items']): ItemRow[] => {
    const mapped: ItemRow[] = items.map((i) => ({
        name: i.name ?? '',
        unit: i.unit ?? '',
        quantity: i.quantity ?? '',
        unit_price: i.unit_price ?? '',
        amount: i.amount ?? '',
    }));

    while (mapped.length < 5) {
        mapped.push(emptyRow());
    }

    return mapped.slice(0, 5);
};

const normalizeToString = (value: string | number | null | undefined): string => {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value);
};

const onlyDigits = (value: string | number | null | undefined): string => normalizeToString(value).replace(/\D/g, '');

const formatCurrencyWithDots = (value: string | number | null | undefined): string => {
    const digits = onlyDigits(value);

    if (!digits) {
        return '';
    }

    return Number(digits).toLocaleString('vi-VN');
};

const parseCurrencyNumber = (value: string | number | null | undefined): number => {
    const digits = onlyDigits(value);

    return digits ? Number(digits) : 0;
};

const parseQuantityNumber = (value: string | number | null | undefined): number => {
    const normalized = normalizeToString(value).replace(',', '.');
    const parsed = Number.parseFloat(normalized);

    return Number.isFinite(parsed) ? parsed : 0;
};

const calculateLineAmount = (quantity: string, unitPrice: string): number => {
    return parseQuantityNumber(quantity) * parseCurrencyNumber(unitPrice);
};

const form = useForm({
    date: props.bill.date ?? '',
    month: props.bill.month ?? '',
    year: props.bill.year ?? '',
    sell_mst: props.bill.sell_mst ?? props.sellMstDefault,
    customer_name: props.bill.customer_name ?? '',
    unit_name: props.bill.unit_name ?? '',
    customer_mst: props.bill.customer_mst ?? '',
    customer_address: props.bill.customer_address ?? '',
    customer_cccd: props.bill.customer_cccd ?? '',
    customer_phone: props.bill.customer_phone ?? '',
    payment_method: props.bill.payment_method ?? '',
    note: props.bill.note ?? '',
    bill_total_currency: props.bill.bill_total_currency ?? '',
    bill_total_text: props.bill.bill_total_text ?? '',
    items: normalizeItems(props.bill.items),
});

const formatItemUnitPrice = (index: number): void => {
    form.items[index].unit_price = formatCurrencyWithDots(form.items[index].unit_price);
};

watch(
    () => form.items.map((item) => `${item.quantity}|${item.unit_price}`),
    () => {
        form.items.forEach((item) => {
            const lineAmount = calculateLineAmount(item.quantity, item.unit_price);
            item.amount = lineAmount > 0 ? lineAmount.toLocaleString('vi-VN') : '';
        });

        const total = form.items.reduce((sum, item) => sum + calculateLineAmount(item.quantity, item.unit_price), 0);
        form.bill_total_currency = total > 0 ? total.toLocaleString('vi-VN') : '';
    },
    { immediate: true },
);

const submit = (): void => {
    form.put(updateBillRoute(props.bill.id).url);
};
</script>

<template>

    <Head :title="`Sua hoa don #${bill.id}`" />

    <div class="mx-auto max-w-5xl space-y-8 p-4 md:p-8">
        <div
            class="flex flex-col gap-4 rounded-2xl border border-border bg-card p-6 shadow-sm sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sua hoa don</p>
                <h1 class="mt-1 text-2xl font-semibold">#{{ bill.id }}</h1>
                <p class="mt-2 font-mono text-sm text-muted-foreground">Ma bi mat: {{ bill.private_key }}</p>
                <p v-if="bill.pdf_url" class="mt-3 text-sm">
                    <a :href="bill.pdf_url ?? '#'" class="font-medium text-primary underline" target="_blank"
                        rel="noopener noreferrer">Mo file PDF da luu</a>
                </p>
                <p v-if="bill.jpg_url" class="mt-1 text-sm">
                    <a :href="bill.jpg_url ?? '#'" class="font-medium text-primary underline" target="_blank"
                        rel="noopener noreferrer">Xem ban anh JPG</a>
                </p>
            </div>
            <Link :href="billsIndex().url"
                class="shrink-0 rounded-lg border border-border px-4 py-2 text-sm hover:bg-muted/80"> Ve danh sach
            </Link>
        </div>

        <form class="space-y-8" @submit.prevent="submit">
            <section class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Ngay thang nam</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div class="grid gap-1">
                        <label class="text-sm font-medium">Ngay</label>
                        <input v-model="form.date"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm" />
                    </div>
                    <div class="grid gap-1">
                        <label class="text-sm font-medium">Thang</label>
                        <input v-model="form.month"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm" />
                    </div>
                    <div class="grid gap-1">
                        <label class="text-sm font-medium">Nam</label>
                        <input v-model="form.year"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm" />
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Thong tin hoa don & ban
                    hang</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="grid gap-1 md:col-span-2">
                        <label class="text-sm font-medium">MST ben ban</label>
                        <input v-model="form.sell_mst"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm font-mono" />
                    </div>
                    <div class="grid gap-1">
                        <label class="text-sm font-medium">Họ tên người mua hàng (Customer’s name)</label>
                        <input v-model="form.customer_name"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm" />
                    </div>
                    <div class="grid gap-1">
                        <label class="text-sm font-medium">Số căn cước công dân (Citizen Identification Number)</label>
                        <input v-model="form.customer_cccd"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm" />
                    </div>
                    <div class="grid gap-1 md:col-span-2">
                        <label class="text-sm font-medium">Địa chỉ (Address)</label>
                        <input v-model="form.customer_address"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm" />
                    </div>
                    <div class="grid gap-1">
                        <label class="text-sm font-medium">Điện thoại (Tel)</label>
                        <input v-model="form.customer_phone"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm" />
                    </div>


                    <div class="grid gap-1">
                        <label class="text-sm font-medium">Hình thức thanh toán (Payment Method)</label>
                        <input v-model="form.payment_method"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm" />
                    </div>

                </div>
            </section>

            <section class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Chi tiet (5 dong)</h2>
                <div class="mt-4 overflow-x-auto rounded-xl border border-border">
                    <table class="w-full min-w-[720px] text-sm">
                        <thead class="border-b bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 font-medium">STT</th>
                                <th class="px-3 py-2 font-medium">Ten hang / dich vu</th>
                                <th class="w-24 px-3 py-2 font-medium">DVT</th>
                                <th class="w-24 px-3 py-2 font-medium">SL</th>
                                <th class="w-32 px-3 py-2 font-medium">Don gia</th>
                                <th class="w-32 px-3 py-2 font-medium">Thanh tien</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(_, idx) in form.items" :key="idx"
                                class="border-b border-border/60 last:border-0">
                                <td class="px-3 py-2 text-muted-foreground">{{ idx + 1 }}</td>
                                <td class="px-2 py-1.5">
                                    <input v-model="form.items[idx].name"
                                        class="w-full rounded border border-input bg-background px-2 py-1.5" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input v-model="form.items[idx].unit"
                                        class="w-full rounded border border-input bg-background px-2 py-1.5" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input v-model="form.items[idx].quantity" type="number" min="0" step="any"
                                        class="w-full rounded border border-input bg-background px-2 py-1.5" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input v-model="form.items[idx].unit_price" inputmode="numeric"
                                        class="w-full rounded border border-input bg-background px-2 py-1.5"
                                        @input="formatItemUnitPrice(idx)" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input v-model="form.items[idx].amount"
                                        class="w-full rounded border border-input bg-muted/50 px-2 py-1.5" readonly />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="form.errors.items" class="mt-2 text-sm text-destructive">{{ form.errors.items }}</p>
            </section>
            <div class="grid gap-1">
                <label class="text-sm font-medium">Tổng tiền thanh toán (Total payment): </label>
                <input v-model="form.bill_total_currency"
                    class="rounded-lg border border-input bg-muted/50 px-3 py-2 text-sm" placeholder="Tu dong tinh"
                    readonly />
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                <Link :href="billsIndex().url"
                    class="rounded-lg border border-border px-5 py-2.5 text-sm hover:bg-muted"> Huy </Link>
                <button type="submit"
                    class="rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow disabled:opacity-50"
                    :disabled="form.processing">
                    {{ form.processing ? 'Dang cap nhat PDF...' : 'Cap nhat & tai tao PDF' }}
                </button>
            </div>
        </form>
    </div>
</template>
