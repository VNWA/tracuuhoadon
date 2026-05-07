<script setup lang="ts">
import type { Header } from 'vue3-easy-data-table';
import EasyDataTable from 'vue3-easy-data-table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { index as staffIndex } from '@/routes/admin/staff';
import {
    create as createBillRoute,
    destroy as destroyBillRoute,
    edit as editBillRoute,
    index as billsIndex,
} from '@/routes/admin/bills';

type BillItem = {
    name: string;
    calculation_unit: string;
    quantity: string;
    unit_price: string;
    amount: string;
};

type Bill = {
    id: number;
    bill_symbol: string;
    bill_date: string | null;
    bill_month: string | null;
    bill_year: string | null;
    bill_sell_mst: string;
    bill_private_key: string;
    customer_name: string | null;
    customer_address: string | null;
    customer_cccd_number: string | null;
    customer_phone: string | null;
    payment_method: string;
    total_amount: string | null;
    pdf_url: string | null;
    created_at: string | null;
    updated_at: string | null;
    user: { id: number | null; name: string | null; email: string | null };
    items: BillItem[];
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const props = defineProps<{
    bills: Paginated<Bill>;
    filters: { search: string; perPage: number };
    canManageStaff: boolean;
}>();

defineOptions({ layout: AppLayout });

const tableHeaders: Header[] = [
    { text: '', value: 'select' },
    { text: 'ID', value: 'id' },
    { text: 'Ky hieu', value: 'bill_symbol' },
    { text: 'MST ben ban', value: 'bill_sell_mst' },
    { text: 'Ma bi mat', value: 'bill_private_key' },
    { text: 'Khach hang', value: 'customer_name' },
    { text: 'Tong tien', value: 'total_amount' },
    { text: 'Nguoi tao', value: 'creator' },
    { text: 'Cap nhat luc', value: 'updated_at' },
    { text: 'Tac vu', value: 'actions' },
];

const search = useForm({
    search: props.filters.search ?? '',
    per_page: props.filters.perPage ?? props.bills.per_page,
    page: props.bills.current_page,
});

const selectedBillIds = ref<number[]>([]);

const allVisibleSelected = computed(() => {
    if (rows.length === 0) {
        return false;
    }

    return rows.every((row) => selectedBillIds.value.includes(row.id));
});

const toggleSelectAll = (): void => {
    if (allVisibleSelected.value) {
        selectedBillIds.value = [];

        return;
    }

    selectedBillIds.value = rows.map((row) => row.id);
};

const toggleSelectedBill = (billId: number): void => {
    if (selectedBillIds.value.includes(billId)) {
        selectedBillIds.value = selectedBillIds.value.filter((id) => id !== billId);

        return;
    }

    selectedBillIds.value.push(billId);
};

const destroyBill = (billId: number): void => {
    if (!confirm('Ban co chac chan muon xoa hoa don nay?')) {
        return;
    }

    router.delete(destroyBillRoute(billId).url, {
        preserveScroll: true,
        onSuccess: () => {
            selectedBillIds.value = selectedBillIds.value.filter((id) => id !== billId);
            applyFilters(props.bills.current_page);
        },
    });
};

const applyFilters = (page = 1): void => {
    router.get(
        billsIndex({
            query: {
                search: search.search || undefined,
                per_page: search.per_page,
                page,
            },
        }),
        {},
        { preserveScroll: true, replace: true },
    );
};

const goToPage = (page: number): void => {
    if (page > 0 && page <= props.bills.last_page) {
        applyFilters(page);
    }
};

const rows = props.bills.data.map((bill) => ({
    ...bill,
    creator: bill.user?.name ?? '-',
}));

const getPdfUrl = (billId: number): string | null => {
    return rows.find((row) => row.id === billId)?.pdf_url ?? null;
};

const destroySelectedBills = (billIds: number[], index = 0): void => {
    if (index >= billIds.length) {
        selectedBillIds.value = [];
        applyFilters(props.bills.current_page);

        return;
    }

    router.delete(destroyBillRoute(billIds[index]).url, {
        preserveScroll: true,
        onSuccess: () => destroySelectedBills(billIds, index + 1),
    });
};

const deleteSelected = (): void => {
    if (selectedBillIds.value.length === 0) {
        return;
    }

    if (!confirm(`Ban co chac chan muon xoa ${selectedBillIds.value.length} hoa don da chon?`)) {
        return;
    }

    destroySelectedBills([...selectedBillIds.value]);
};
</script>

<template>
    <Head title="Admin - Quan ly hoa don" />

    <div class="space-y-6 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Admin / Quan ly hoa don</h1>
            <div class="flex items-center gap-3">
                <Link :href="createBillRoute().url" class="rounded bg-black px-4 py-2 text-sm text-white">Tao bill moi</Link>
                <a v-if="canManageStaff" :href="staffIndex().url" class="text-sm underline">Quan ly staff</a>
            </div>
        </div>

        <div class="space-y-3 rounded-lg border p-4">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="rounded border px-3 py-2 text-sm" @click="toggleSelectAll">
                    {{ allVisibleSelected ? 'Bo chon tat ca' : 'Chon tat ca' }}
                </button>
                <button
                    type="button"
                    class="rounded border border-red-200 px-3 py-2 text-sm text-red-700 disabled:opacity-50"
                    :disabled="selectedBillIds.length === 0"
                    @click="deleteSelected"
                >
                    Xoa da chon ({{ selectedBillIds.length }})
                </button>
                <input
                    v-model="search.search"
                    class="rounded border px-3 py-2 text-sm"
                    placeholder="Tim theo MST, ma bi mat, ten khach..."
                    @keyup.enter="applyFilters(1)"
                />
                <select v-model.number="search.per_page" class="rounded border px-3 py-2 text-sm" @change="applyFilters(1)">
                    <option :value="10">10 / trang</option>
                    <option :value="20">20 / trang</option>
                    <option :value="50">50 / trang</option>
                </select>
                <button type="button" class="rounded border px-3 py-2 text-sm" @click="applyFilters(1)">Loc</button>
            </div>

            <EasyDataTable :headers="tableHeaders" :items="rows" hide-footer>
                <template #item-select="{ id }">
                    <input
                        type="checkbox"
                        class="h-4 w-4"
                        :checked="selectedBillIds.includes(id)"
                        @change="toggleSelectedBill(id)"
                    />
                </template>
                <template #item-actions="{ id }">
                    <div class="flex gap-2">
                        <a
                            :href="getPdfUrl(id) ?? '#'"
                            class="rounded border px-2 py-1 text-xs"
                            :class="{ 'pointer-events-none opacity-50': !getPdfUrl(id) }"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            View
                        </a>
                        <Link :href="editBillRoute(id).url" class="rounded border px-2 py-1 text-xs">Sua</Link>
                        <button class="rounded border px-2 py-1 text-xs text-red-700" @click="destroyBill(id)">Xoa</button>
                    </div>
                </template>
            </EasyDataTable>

            <div class="flex items-center justify-between text-sm">
                <p>Trang {{ bills.current_page }}/{{ bills.last_page }} - Tong {{ bills.total }} hoa don</p>
                <div class="flex gap-2">
                    <button class="rounded border px-3 py-1" :disabled="bills.current_page <= 1" @click="goToPage(bills.current_page - 1)">
                        Truoc
                    </button>
                    <button class="rounded border px-3 py-1" :disabled="bills.current_page >= bills.last_page" @click="goToPage(bills.current_page + 1)">
                        Sau
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
