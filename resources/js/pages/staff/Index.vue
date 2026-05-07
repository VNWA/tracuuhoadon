<script setup lang="ts">
import type { Header } from 'vue3-easy-data-table';
import EasyDataTable from 'vue3-easy-data-table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { index as billsIndex } from '@/routes/admin/bills';
import {
    create as createStaffRoute,
    destroy as destroyStaffRoute,
    edit as editStaffRoute,
    index as staffIndex,
} from '@/routes/admin/staff';

type Staff = {
    id: number;
    name: string;
    email: string;
    created_at: string | null;
    updated_at: string | null;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const props = defineProps<{
    staff: Paginated<Staff>;
    filters: { search: string; perPage: number };
}>();

defineOptions({ layout: AppLayout });

const headers: Header[] = [
    { text: 'ID', value: 'id' },
    { text: 'Ten', value: 'name' },
    { text: 'Email', value: 'email' },
    { text: 'Tao luc', value: 'created_at' },
    { text: 'Cap nhat luc', value: 'updated_at' },
    { text: 'Tac vu', value: 'actions' },
];

const search = useForm({
    search: props.filters.search ?? '',
    per_page: props.filters.perPage ?? props.staff.per_page,
    page: props.staff.current_page,
});

const destroyStaff = (staffId: number): void => {
    if (!confirm('Ban co chac chan muon xoa staff nay?')) {
        return;
    }

    router.delete(destroyStaffRoute(staffId).url, { preserveScroll: true });
};

const applyFilters = (page = 1): void => {
    router.get(
        staffIndex({
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
    if (page > 0 && page <= props.staff.last_page) {
        applyFilters(page);
    }
};
</script>

<template>
    <Head title="Admin - Quan ly staff" />

    <div class="space-y-6 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Admin / Quan ly staff</h1>
            <div class="flex items-center gap-3">
                <Link :href="createStaffRoute().url" class="rounded bg-black px-4 py-2 text-sm text-white">Tao staff moi</Link>
                <a :href="billsIndex().url" class="text-sm underline">Quan ly hoa don</a>
            </div>
        </div>

        <div class="space-y-3 rounded-lg border p-4">
            <div class="flex flex-wrap items-center gap-2">
                <input
                    v-model="search.search"
                    class="rounded border px-3 py-2 text-sm"
                    placeholder="Tim theo ten hoac email..."
                    @keyup.enter="applyFilters(1)"
                />
                <select v-model.number="search.per_page" class="rounded border px-3 py-2 text-sm" @change="applyFilters(1)">
                    <option :value="10">10 / trang</option>
                    <option :value="20">20 / trang</option>
                    <option :value="50">50 / trang</option>
                </select>
                <button type="button" class="rounded border px-3 py-2 text-sm" @click="applyFilters(1)">Loc</button>
            </div>

            <EasyDataTable :headers="headers" :items="staff.data" hide-footer>
                <template #item-actions="{ id }">
                    <div class="flex gap-2">
                        <Link :href="editStaffRoute(id).url" class="rounded border px-2 py-1 text-xs">Sua</Link>
                        <button class="rounded border px-2 py-1 text-xs text-red-700" @click="destroyStaff(id)">Xoa</button>
                    </div>
                </template>
            </EasyDataTable>

            <div class="flex items-center justify-between text-sm">
                <p>Trang {{ staff.current_page }}/{{ staff.last_page }} - Tong {{ staff.total }} staff</p>
                <div class="flex gap-2">
                    <button class="rounded border px-3 py-1" :disabled="staff.current_page <= 1" @click="goToPage(staff.current_page - 1)">
                        Truoc
                    </button>
                    <button class="rounded border px-3 py-1" :disabled="staff.current_page >= staff.last_page" @click="goToPage(staff.current_page + 1)">
                        Sau
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
