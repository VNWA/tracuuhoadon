<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { index as billsIndex, update as updateBillRoute } from '@/routes/admin/bills';

type BillItem = {
    name: string;
    calculation_unit: string;
    quantity: string;
    unit_price: string;
    amount: string;
};

type Bill = {
    id: number;
    bill_date: string | null;
    bill_month: string | null;
    bill_year: string | null;
    customer_name: string | null;
    customer_address: string | null;
    customer_cccd_number: string | null;
    customer_phone: string | null;
    payment_method: string;
    total_amount: string | null;
    items: BillItem[];
};

const props = defineProps<{ bill: Bill }>();

defineOptions({ layout: AppLayout });

const emptyItem = (): BillItem => ({
    name: '',
    calculation_unit: '',
    quantity: '',
    unit_price: '',
    amount: '',
});

const form = useForm({
    bill_date: props.bill.bill_date ?? '',
    bill_month: props.bill.bill_month ?? '',
    bill_year: props.bill.bill_year ?? '',
    customer_name: props.bill.customer_name ?? '',
    customer_address: props.bill.customer_address ?? '',
    customer_cccd_number: props.bill.customer_cccd_number ?? '',
    customer_phone: props.bill.customer_phone ?? '',
    payment_method: props.bill.payment_method,
    total_amount: props.bill.total_amount ?? '',
    items: [props.bill.items[0] ? { ...props.bill.items[0] } : emptyItem()],
});

const item = form.items[0];

const submit = (): void => {
    form.put(updateBillRoute(props.bill.id).url);
};
</script>

<template>
    <Head title="Cap nhat bill" />

    <div class="mx-auto max-w-6xl space-y-6 p-4">
        <div class="flex items-center justify-between rounded-xl border bg-card p-4 shadow-sm">
            <h1 class="text-2xl font-semibold">Cap nhat bill #{{ bill.id }}</h1>
            <Link :href="billsIndex().url" class="rounded-md border px-3 py-2 text-sm font-medium hover:bg-muted">Quay lai danh sach</Link>
        </div>

        <form class="space-y-6 rounded-xl border bg-card p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-3">
                <h2 class="text-sm font-semibold uppercase text-muted-foreground">Thong tin khach hang</h2>
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="grid gap-1 md:col-span-2">
                        <p class="text-xs font-medium text-muted-foreground">Ngay / Thang / Nam hoa don</p>
                        <div class="grid grid-cols-3 gap-2">
                            <input v-model="form.bill_date" class="rounded-md border px-3 py-2 text-sm" placeholder="Ngay (VD: 07)" />
                            <input v-model="form.bill_month" class="rounded-md border px-3 py-2 text-sm" placeholder="Thang (VD: 05)" />
                            <input v-model="form.bill_year" class="rounded-md border px-3 py-2 text-sm" placeholder="Nam (VD: 2026)" />
                        </div>
                    </div>
                    <input v-model="form.customer_name" class="rounded-md border px-3 py-2 text-sm" placeholder="Ten nguoi mua" />
                    <input v-model="form.customer_phone" class="rounded-md border px-3 py-2 text-sm" placeholder="So dien thoai" />
                    <input v-model="form.customer_cccd_number" class="rounded-md border px-3 py-2 text-sm" placeholder="So CCCD" />
                    <input v-model="form.customer_address" class="rounded-md border px-3 py-2 text-sm" placeholder="Dia chi" />
                    <input v-model="form.payment_method" class="rounded-md border px-3 py-2 text-sm" placeholder="Hinh thuc thanh toan" />
                    <input v-model="form.total_amount" class="rounded-md border px-3 py-2 text-sm md:col-span-2" placeholder="Tong tien thanh toan (VD: 1500000)" />
                </div>
            </div>

            <div class="space-y-3 rounded-lg border bg-muted/20 p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold uppercase text-muted-foreground">Thong tin san pham</h2>
                    <span class="text-xs text-muted-foreground">Chi nhap 1 item</span>
                </div>
                <div class="grid gap-3 md:grid-cols-5">
                    <input v-model="item.name" class="rounded-md border bg-background px-3 py-2 text-sm" placeholder="Ten san pham" />
                    <input v-model="item.calculation_unit" class="rounded-md border bg-background px-3 py-2 text-sm" placeholder="Don vi tinh" />
                    <input v-model="item.quantity" class="rounded-md border bg-background px-3 py-2 text-sm" placeholder="So luong" />
                    <input v-model="item.unit_price" class="rounded-md border bg-background px-3 py-2 text-sm" placeholder="Don gia" />
                    <input v-model="item.amount" class="rounded-md border bg-background px-3 py-2 text-sm" placeholder="Thanh tien" />
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <Link :href="billsIndex().url" class="rounded-md border px-5 py-2 text-sm font-medium hover:bg-muted">Huy</Link>
                <button type="submit" class="rounded-md bg-black px-5 py-2 text-sm font-medium text-white disabled:opacity-60" :disabled="form.processing">
                    Cap nhat
                </button>
            </div>
        </form>
    </div>
</template>
