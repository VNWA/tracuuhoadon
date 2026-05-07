<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { index as staffIndex, update as updateStaffRoute } from '@/routes/admin/staff';

type Staff = {
    id: number;
    name: string;
    email: string;
};

const props = defineProps<{ staff: Staff }>();

defineOptions({ layout: AppLayout });

const form = useForm({
    name: props.staff.name,
    email: props.staff.email,
    password: '',
    password_confirmation: '',
});

const submit = (): void => {
    form.put(updateStaffRoute(props.staff.id).url);
};
</script>

<template>
    <Head title="Cap nhat staff" />

    <div class="space-y-6 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Cap nhat staff #{{ staff.id }}</h1>
            <Link :href="staffIndex().url" class="text-sm underline">Quay lai danh sach</Link>
        </div>

        <form class="space-y-3 rounded-lg border p-4" @submit.prevent="submit">
            <div class="grid gap-3 md:grid-cols-2">
                <input v-model="form.name" class="rounded border px-3 py-2 text-sm" placeholder="Ho ten" />
                <input v-model="form.email" class="rounded border px-3 py-2 text-sm" placeholder="Email" />
                <input v-model="form.password" type="password" class="rounded border px-3 py-2 text-sm" placeholder="Mat khau moi (neu doi)" />
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    class="rounded border px-3 py-2 text-sm"
                    placeholder="Xac nhan mat khau"
                />
            </div>

            <button type="submit" class="rounded bg-black px-4 py-2 text-sm text-white" :disabled="form.processing">Cap nhat</button>
        </form>
    </div>
</template>
