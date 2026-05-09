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

type Bill = {
    id: number;
    private_key: string;
    date: string | null;
    month: string | null;
    year: string | null;
    sell_mst: string;
    customer_name: string | null;
    pdf_url: string | null;
    jpg_url: string | null;
    created_at: string | null;
    updated_at: string | null;
    user: { id: number | null; name: string | null; email: string | null };
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
    { text: 'Khach hang', value: 'customer_name' },
    { text: 'Ngay HD', value: 'invoice_date' },
    { text: 'MST ban', value: 'sell_mst' },
    { text: 'Ma bi mat', value: 'private_key' },
    { text: 'Nguoi tao', value: 'creator' },
    { text: 'PDF', value: 'pdf_status' },
    { text: 'Cap nhat', value: 'updated_at' },
    { text: 'Tac vu', value: 'actions' },
];

const search = useForm({
    search: props.filters.search ?? '',
    per_page: props.filters.perPage ?? props.bills.per_page,
    page: props.bills.current_page,
});

const selectedBillIds = ref<number[]>([]);

const allVisibleSelected = computed(() => {
    if (rows.value.length === 0) {
        return false;
    }

    return rows.value.every((row) => selectedBillIds.value.includes(row.id));
});

const rows = computed(() =>
    props.bills.data.map((bill) => ({
        ...bill,
        customer_name: bill.customer_name?.trim() || '-',
        creator: bill.user?.name ?? '-',
        invoice_date: [bill.date, bill.month, bill.year].filter(Boolean).join('/') || '-',
        pdf_status: bill.pdf_url ? 'Da co PDF' : 'Chua co',
    })),
);

const toggleSelectAll = (): void => {
    if (allVisibleSelected.value) {
        selectedBillIds.value = [];

        return;
    }

    selectedBillIds.value = rows.value.map((row) => row.id);
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

const getPdfUrl = (billId: number): string | null => {
    return rows.value.find((row) => row.id === billId)?.pdf_url ?? null;
};

const getJpgUrl = (billId: number): string | null => {
    return rows.value.find((row) => row.id === billId)?.jpg_url ?? null;
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

    <div class="space-y-6 p-4 md:p-8">
        <div
            class="flex flex-col gap-4 rounded-2xl border border-border bg-card px-6 py-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-foreground">Quan ly hoa don</h1>
                <p class="mt-1 text-sm text-muted-foreground">Tao moi co form day du; sau khi luu he thong tao PDF va luu duong dan file.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="createBillRoute().url"
                    class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition hover:opacity-90"
                >
                    Tao hoa don
                </Link>
                <a
                    v-if="canManageStaff"
                    :href="staffIndex().url"
                    class="inline-flex rounded-lg border border-border px-4 py-2.5 text-sm font-medium hover:bg-muted/80"
                >
                    Quan ly staff
                </a>
            </div>
        </div>

        <div class="space-y-4 rounded-2xl border border-border bg-card p-4 shadow-sm md:p-6">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-border px-3 py-2 text-sm hover:bg-muted/60"
                    @click="toggleSelectAll"
                >
                    {{ allVisibleSelected ? 'Bo chon tat ca' : 'Chon tat ca' }}
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-destructive/30 px-3 py-2 text-sm text-destructive disabled:opacity-50"
                    :disabled="selectedBillIds.length === 0"
                    @click="deleteSelected"
                >
                    Xoa da chon ({{ selectedBillIds.length }})
                </button>
                <input
                    v-model="search.search"
                    class="min-w-[200px] flex-1 rounded-lg border border-input bg-background px-3 py-2 text-sm"
                    placeholder="Tim theo ma bi mat, MST, ten khach..."
                    @keyup.enter="applyFilters(1)"
                />
                <select v-model.number="search.per_page" class="rounded-lg border border-input bg-background px-3 py-2 text-sm" @change="applyFilters(1)">
                    <option :value="10">10 / trang</option>
                    <option :value="20">20 / trang</option>
                    <option :value="50">50 / trang</option>
                </select>
                <button type="button" class="rounded-lg bg-secondary px-3 py-2 text-sm hover:bg-secondary/80" @click="applyFilters(1)">Loc</button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-border">
                <EasyDataTable class="w-full text-sm" :headers="tableHeaders" :items="rows" hide-footer>
                    <template #item-select="{ id }">
                        <input type="checkbox" class="size-4 rounded border-input accent-primary" :checked="selectedBillIds.includes(id)" @change="toggleSelectedBill(id)" />
                    </template>
                    <template #item-pdf_status="{ pdf_status }">
                        <span
                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="pdf_status === 'Da co PDF' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400' : 'bg-amber-500/15 text-amber-800 dark:text-amber-300'"
                        >
                            {{ pdf_status }}
                        </span>
                    </template>
                    <template #item-actions="{ id }">
                        <div class="flex flex-wrap gap-1.5">
                            <a
                                :href="getPdfUrl(id) ?? '#'"
                                class="rounded-md border px-2 py-1 text-xs transition hover:bg-muted"
                                :class="{ 'pointer-events-none opacity-40': !getPdfUrl(id) }"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Xem PDF
                            </a>
                            <a
                                :href="getJpgUrl(id) ?? '#'"
                                class="rounded-md border px-2 py-1 text-xs transition hover:bg-muted"
                                :class="{ 'pointer-events-none opacity-40': !getJpgUrl(id) }"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Xem JPG
                            </a>
                            <Link :href="editBillRoute(id).url" class="rounded-md border border-primary/30 px-2 py-1 text-xs font-medium hover:bg-primary/10"> Sua </Link>
                            <button class="rounded-md border border-destructive/30 px-2 py-1 text-xs text-destructive hover:bg-destructive/10" @click="destroyBill(id)">
                                Xoa
                            </button>
                        </div>
                    </template>
                </EasyDataTable>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-muted-foreground">
                <p>Trang {{ bills.current_page }}/{{ bills.last_page }} - Tong {{ bills.total }} hoa don</p>
                <div class="flex gap-2">
                    <button
                        class="rounded-lg border px-3 py-1.5 hover:bg-muted disabled:opacity-40"
                        type="button"
                        :disabled="bills.current_page <= 1"
                        @click="goToPage(bills.current_page - 1)"
                    >
                        Truoc
                    </button>
                    <button
                        class="rounded-lg border px-3 py-1.5 hover:bg-muted disabled:opacity-40"
                        type="button"
                        :disabled="bills.current_page >= bills.last_page"
                        @click="goToPage(bills.current_page + 1)"
                    >
                        Sau
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
