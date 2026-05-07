<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { index as staffIndex, store as storeStaffRoute } from '@/routes/admin/staff';

defineOptions({ layout: AppLayout });

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = (): void => {
    form.post(storeStaffRoute().url);
};
</script>

<template>
    <Head title="Tao staff moi" />

    <div class="space-y-6 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Tao staff moi</h1>
            <Link :href="staffIndex().url" class="text-sm underline">Quay lai danh sach</Link>
        </div>

        <form class="space-y-3 rounded-lg border p-4" @submit.prevent="submit">
            <div class="grid gap-3 md:grid-cols-2">
                <input v-model="form.name" class="rounded border px-3 py-2 text-sm" placeholder="Ho ten" />
                <input v-model="form.email" class="rounded border px-3 py-2 text-sm" placeholder="Email" />
                <input v-model="form.password" type="password" class="rounded border px-3 py-2 text-sm" placeholder="Mat khau" />
                <input v-model="form.password_confirmation" type="password" class="rounded border px-3 py-2 text-sm" placeholder="Xac nhan mat khau" />
            </div>

            <button type="submit" class="rounded bg-black px-4 py-2 text-sm text-white" :disabled="form.processing">Tao staff</button>
        </form>
    </div>
</template>
