<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { index as billsIndex, upload as uploadBillRoute } from '@/routes/admin/bills';

type Bill = {
    id: number;
    bill_symbol: string;
    bill_date: string | null;
    bill_month: string | null;
    bill_year: string | null;
    bill_sell_mst: string;
    bill_private_key: string;
    bill_path: string | null;
    bill_demo_path: string | null;
    demo_download_url: string | null;
    pdf_url: string | null;
    created_at: string | null;
    updated_at: string | null;
};

const props = defineProps<{ bill: Bill }>();

defineOptions({ layout: AppLayout });

const fileInput = ref<HTMLInputElement | null>(null);
const uploading = ref(false);

const invoiceDateDisplay = [props.bill.bill_date, props.bill.bill_month, props.bill.bill_year].filter(Boolean).join('/');

const triggerFilePick = (): void => {
    fileInput.value?.click();
};

const onFileSelected = (event: Event): void => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    if (!file) {
        return;
    }

    uploading.value = true;

    router.post(
        uploadBillRoute(props.bill.id).url,
        { bill_file: file },
        {
            preserveScroll: true,
            forceFormData: true,
            onFinish: () => {
                uploading.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="'Hoa don #' + bill.id" />

    <div class="mx-auto max-w-3xl space-y-6 p-4 md:p-8">
        <div class="flex flex-col gap-4 rounded-2xl border border-border bg-card px-6 py-5 shadow-sm sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Chinh sua hoa don</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">Ma #{{ bill.id }} — {{ bill.bill_symbol }}</h1>
                <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">Ngay lap (tu dong luc tao)</dt>
                        <dd class="font-medium">{{ invoiceDateDisplay || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">MST ben ban</dt>
                        <dd class="font-mono text-sm">{{ bill.bill_sell_mst }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-muted-foreground">Ma bi mat</dt>
                        <dd class="font-mono text-sm break-all">{{ bill.bill_private_key }}</dd>
                    </div>
                </dl>
            </div>
            <Link :href="billsIndex().url" class="shrink-0 rounded-lg border border-border px-4 py-2 text-sm hover:bg-muted/80">
                Ve danh sach
            </Link>
        </div>

        <div class="rounded-2xl border border-border bg-card p-6 shadow-sm">
            <h2 class="text-lg font-semibold">Tai len hoa don</h2>
            <p class="mt-1 text-sm text-muted-foreground">Tai ban mau PDF ve may, chinh sua roi tai len bang nut ben duoi. File chi nhan dinh dang PDF.</p>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <a
                    v-if="bill.demo_download_url"
                    :href="bill.demo_download_url"
                    class="inline-flex flex-1 items-center justify-center rounded-xl border border-border bg-background px-4 py-3 text-sm font-medium shadow-sm transition hover:bg-muted"
                >
                    Tai ban mau ve
                </a>
                <button
                    v-else
                    type="button"
                    disabled
                    class="inline-flex flex-1 cursor-not-allowed items-center justify-center rounded-xl border border-dashed px-4 py-3 text-sm text-muted-foreground opacity-70"
                >
                    Chua co ban mau
                </button>

                <input ref="fileInput" type="file" accept=".pdf,application/pdf" class="hidden" @change="onFileSelected" />

                <button
                    type="button"
                    class="inline-flex flex-1 items-center justify-center rounded-xl bg-primary px-4 py-3 text-sm font-medium text-primary-foreground shadow-sm transition hover:opacity-90 disabled:opacity-50"
                    :disabled="uploading"
                    @click="triggerFilePick"
                >
                    {{ uploading ? 'Dang tai len...' : 'Tai len hoa don (PDF)' }}
                </button>
            </div>

            <div
                class="mt-6 rounded-xl border px-4 py-3 text-sm"
                :class="
                    bill.pdf_url
                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-900 dark:text-emerald-100'
                        : 'border-amber-500/30 bg-amber-500/10 text-amber-900 dark:text-amber-100'
                "
            >
                <template v-if="bill.pdf_url">
                    Da co file tai len.
                    <a :href="bill.pdf_url" class="font-medium underline" target="_blank" rel="noopener noreferrer">Mo xem PDF</a>
                </template>
                <template v-else> Chua co file tai len — khach hang se khong mo duoc lien ket xem tur danh sach cho den khi ban tai PDF len. </template>
            </div>
        </div>
    </div>
</template>
