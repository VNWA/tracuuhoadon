<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { index as billsIndex, store as storeBillRoute } from '@/routes/admin/bills';

type ItemRow = {
    name: string;
    unit: string;
    quantity: string;
    unit_price: string;
    amount: string;
};

const props = defineProps<{
    sellMstDefault: string;
}>();

defineOptions({ layout: AppLayout });

const pad2 = (n: number): string => String(n).padStart(2, '0');

const today = ((): { date: string; month: string; year: string } => {
    const now = new Date();

    return {
        date: pad2(now.getDate()),
        month: pad2(now.getMonth() + 1),
        year: String(now.getFullYear()),
    };
})();

const emptyRow = (): ItemRow => ({
    name: '',
    unit: '',
    quantity: '',
    unit_price: '',
    amount: '',
});

/** Dong hang dau tien mac dinh — ban sua gia tri tai day */
const defaultFirstBillItemRow = (): ItemRow => ({
    name: 'VÀNG MIẾNG SJC 1 (Chỉ)  ',
    unit: 'Chỉ',
    quantity: '1',
    unit_price: '16.870.000',
    amount: '16.870.000',
});

const form = useForm({
    date: today.date,
    month: today.month,
    year: today.year,
    sell_mst: props.sellMstDefault,
    customer_name: 'Trần Thiện Tuấn ',
    unit_name: '',
    customer_mst: '',
    customer_address: '29/71A Đoàn Thị Điểm, P1, Phú Nhuận, TP Hồ Chí Minh  ',
    customer_cccd: '079088037381',
    customer_phone: '0927147686',
    payment_method: ' Chuyển khoản ',
    note: '',
    bill_total_currency: '16.870.000',
    bill_total_text: '',
    items: [defaultFirstBillItemRow(), ...Array.from({ length: 4 }, () => emptyRow())],
});

const submit = (): void => {
    form.post(storeBillRoute().url);
};
</script>

<template>

    <Head title="Tao hoa don" />

    <div class="mx-auto max-w-5xl space-y-8 p-4 md:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Tao hoa don</h1>
                <p class="mt-1 text-sm text-muted-foreground">Nhap du lieu va luu. He thong tu sinh ma bi mat va file
                    PDF tu ban mau invoice.</p>
            </div>
            <Link :href="billsIndex().url"
                class="inline-flex rounded-lg border border-border px-4 py-2 text-sm hover:bg-muted/80"> Ve danh sach
            </Link>
        </div>

        <form class="space-y-8" @submit.prevent="submit">
            <section class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Ngay thang nam (mac dinh
                    hom nay)</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div class="grid gap-1">
                        <label class="text-sm font-medium">Ngay</label>
                        <input v-model="form.date"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm"
                            placeholder="VD: 08" />
                    </div>
                    <div class="grid gap-1">
                        <label class="text-sm font-medium">Thang</label>
                        <input v-model="form.month"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm"
                            placeholder="VD: 05" />
                    </div>
                    <div class="grid gap-1">
                        <label class="text-sm font-medium">Nam</label>
                        <input v-model="form.year"
                            class="rounded-lg border border-input bg-background px-3 py-2 text-sm"
                            placeholder="VD: 2026" />
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
                        <label class="text-sm font-medium">Số căn cước công dân (Citizen Identification Number):</label>
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
                            <tr v-for="(row, idx) in form.items" :key="idx"
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
                                    <input v-model="form.items[idx].quantity"
                                        class="w-full rounded border border-input bg-background px-2 py-1.5" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input v-model="form.items[idx].unit_price"
                                        class="w-full rounded border border-input bg-background px-2 py-1.5" />
                                </td>
                                <td class="px-2 py-1.5">
                                    <input v-model="form.items[idx].amount"
                                        class="w-full rounded border border-input bg-background px-2 py-1.5" />
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
                    class="rounded-lg border border-input bg-background px-3 py-2 text-sm"
                    placeholder="VD: 1.000.000" />
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                <Link :href="billsIndex().url"
                    class="rounded-lg border border-border px-5 py-2.5 text-sm hover:bg-muted"> Huy </Link>
                <button type="submit"
                    class="rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow disabled:opacity-50"
                    :disabled="form.processing">
                    {{ form.processing ? 'Dang luu...' : 'Luu & tao PDF' }}
                </button>
            </div>
        </form>
    </div>
</template>
