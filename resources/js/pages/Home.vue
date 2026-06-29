<script setup lang="ts">
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import { pdf } from '@/routes/public-bill';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import PdfPreview from '@/components/PdfPreview.vue';
import { PrinterIcon } from 'lucide-vue-next';

const props = defineProps<{
    lookup: { bill_sell_mst?: string; bill_private_key?: string } | null;
    pdfUrl: string | null;
    lookupError?: string | null;
}>();

const form = ref({
    bill_sell_mst: props.lookup?.bill_sell_mst ?? '',
    bill_private_key: props.lookup?.bill_private_key ?? '',
});

const touched = ref({
    bill_sell_mst: false,
    bill_private_key: false,
});

const isLoading = ref(false);
const lookupError = ref<string | null>(props.lookupError ?? null);
const pdfPreviewUrl = ref<string | null>(props.pdfUrl ?? null);

const clientErrors = computed(() => {
    const errors: Record<string, string> = {};

    if (!form.value.bill_sell_mst.trim()) {
        errors.bill_sell_mst = 'Vui long nhap MST ben ban.';
    }

    if (!form.value.bill_private_key.trim()) {
        errors.bill_private_key = 'Vui long nhap ma so bi mat.';
    }

    return errors;
});

const markTouched = (field: 'bill_sell_mst' | 'bill_private_key'): void => {
    touched.value[field] = true;
};

const displayError = (field: 'bill_sell_mst' | 'bill_private_key'): string => {
    if (touched.value[field] && clientErrors.value[field]) {
        return clientErrors.value[field] as string;
    }

    return '';
};

const submit = async (): Promise<void> => {
    touched.value.bill_sell_mst = true;
    touched.value.bill_private_key = true;
    lookupError.value = null;

    if (Object.keys(clientErrors.value).length > 0) {
        return;
    }

    isLoading.value = true;

    try {
        const response = await axios.get(
            pdf.url({
                query: {
                    bill_sell_mst: form.value.bill_sell_mst,
                    bill_private_key: form.value.bill_private_key,
                },
            }),
            { responseType: 'blob' },
        );

        if (pdfPreviewUrl.value?.startsWith('blob:')) {
            URL.revokeObjectURL(pdfPreviewUrl.value);
        }

        pdfPreviewUrl.value = URL.createObjectURL(response.data);
    } catch {
        if (pdfPreviewUrl.value?.startsWith('blob:')) {
            URL.revokeObjectURL(pdfPreviewUrl.value);
        }

        pdfPreviewUrl.value = null;
        lookupError.value = 'Khong tim thay hoa don voi thong tin da nhap.';
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    document.documentElement.classList.remove('dark');
});

onBeforeUnmount(() => {
    if (pdfPreviewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(pdfPreviewUrl.value);
    }
});
</script>

<template>

    <Head title="Tra cứu hóa đơn" />

    <div class="min-h-screen bg-[#f3f3f3] text-[#333]">
        <!-- Header -->
        <header class="flex h-14 items-center border-b border-gray-200 bg-white px-4 sm:px-6">
            <div class="flex items-center gap-3 sm:gap-6">
                <!-- Logo -->
                <div class="flex shrink-0 items-center gap-2">
                    <img src="/invoice_logo.png" alt="logo" class="h-8 w-auto max-w-[160px] sm:h-10 sm:max-w-none">
                </div>

                <!-- Menu -->
                <button
                    class="flex h-10 w-10 items-center justify-center rounded text-[#6b7a90] hover:bg-gray-100 lg:hidden"
                    type="button"
                    aria-label="Menu"
                >
                    ☰
                </button>
            </div>
        </header>

        <div class="flex">
            <!-- Sidebar -->
            <aside class="hidden min-h-[calc(100vh-3.5rem)] w-[265px] shrink-0 bg-[#3d4c68] lg:block" />

            <!-- Content -->
            <main class="min-w-0 flex-1 p-4 sm:p-6 lg:p-7">
                <!-- Breadcrumb -->
                <div class="mb-4 flex flex-wrap items-center gap-2 text-xs text-gray-500 sm:mb-5 sm:gap-3 sm:text-[13px]">
                    <span>TIỆN ÍCH</span>
                    <span>›</span>

                    <span class="font-semibold text-gray-700">TRA CỨU HÓA ĐƠN</span>
                </div>

                <!-- Card -->
                <div class="bg-white border border-gray-200">
                    <!-- Card Header -->
                    <div class="flex min-h-[52px] items-center justify-between border-b border-gray-200 px-4">
                        <h2 class="text-sm font-semibold text-gray-700 sm:text-[14px]">
                            THÔNG TIN TÌM KIẾM
                        </h2>

                        <button class="text-lg text-gray-500" type="button" aria-label="Thu gon">⌄</button>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 sm:p-6 lg:p-8">
                        <!-- Row -->
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-7">
                            <!-- MST -->
                            <div class="grid grid-cols-1 gap-2 lg:grid-cols-12">
                                <label
                                    class="block text-sm font-semibold text-gray-700 lg:col-span-4 lg:text-end lg:text-[14px]"
                                >
                                    MST bên bán
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="lg:col-span-8">
                                    <input v-model="form.bill_sell_mst" type="text"
                                        class="h-[34px] w-full rounded-sm px-3 outline-none focus:ring-0" :class="displayError('bill_sell_mst')
                                            ? 'border border-red-500'
                                            : 'border border-gray-300 focus:border-blue-400'
                                            " @blur="markTouched('bill_sell_mst')" />

                                    <p v-if="displayError('bill_sell_mst')" class="mt-1 text-[13px] text-red-500">
                                        {{ displayError('bill_sell_mst') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="grid grid-cols-1 gap-2 lg:grid-cols-12">
                                <label
                                    class="block text-sm font-semibold text-gray-700 lg:col-span-4 lg:text-end lg:text-[14px]"
                                >
                                    Mã số bí mật
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="lg:col-span-8">
                                    <input v-model="form.bill_private_key" type="text"
                                        class="h-[34px] w-full rounded-sm px-3 outline-none" :class="displayError('bill_private_key')
                                            ? 'border border-red-500'
                                            : 'border border-gray-300 focus:border-blue-400'
                                            " @blur="markTouched('bill_private_key')" />
                                    <p v-if="displayError('bill_private_key')" class="mt-1 text-[13px] text-red-500">
                                        {{ displayError('bill_private_key') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="lookupError"
                            class="mt-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                            {{ lookupError }}
                        </div>

                        <!-- Submit -->
                        <div class="mt-6 flex justify-center sm:mt-7">
                            <button type="button"
                                class="flex h-[34px] items-center gap-2 rounded bg-red-600 px-5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60 sm:text-[14px]"
                                :disabled="isLoading" @click="submit">
                                <span class="text-[16px]">🔎</span>
                                <span>{{ isLoading ? 'Dang tim...' : 'Tìm kiếm' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="pdfPreviewUrl" class="mt-4 overflow-hidden rounded border border-gray-200 bg-white sm:mt-6">
                    <div class="flex flex-col items-center gap-3 px-3 py-4 sm:flex-row sm:flex-wrap sm:justify-center sm:gap-4 sm:px-4 sm:py-5">
                        <ul class="flex flex-wrap items-center justify-center gap-x-3 gap-y-2 text-xs font-bold text-red-500 underline sm:gap-4 sm:text-sm">
                            <li>Tải về file PDF</li>
                            <li>Tải về file ZIp</li>
                            <li>Tải chứng thư số</li>
                        </ul>
                        <button
                            type="button"
                            class="flex shrink-0 items-center justify-center gap-2 rounded bg-red-600 px-3 py-1.5 text-base font-bold text-white sm:text-lg"
                        >
                            <PrinterIcon class="h-4 w-4" />
                            <span>In</span>
                        </button>
                    </div>
                    <PdfPreview v-if="pdfPreviewUrl" :src="pdfPreviewUrl" />
                </div>

                <!-- Footer -->
                <footer
                    class="mt-12 flex flex-col items-center gap-2 border-t border-gray-300 pt-6 text-center text-[11px] text-gray-500 sm:mt-24 sm:flex-row sm:items-center sm:justify-between sm:text-left sm:text-[12px] lg:mt-48"
                >
                    <div>
                        ©2016-2026 Bản quyền thuộc về Tập đoàn Công nghiệp - Viễn thông
                        Quân đội Viettel
                    </div>

                    <div class="shrink-0">Hóa đơn điện tử SInvoice</div>
                </footer>
            </main>
        </div>
    </div>
</template>
