<script setup lang="ts">
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import { pdf } from '@/routes/public-bill';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { DownloadIcon, FileIcon, PrinterIcon } from 'lucide-vue-next';

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
        <header class="h-[56px] bg-white border-b border-gray-200 flex items-center px-6">
            <div class="flex items-center gap-6">
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <img src="/invoice_logo.png" alt="logo" class="w-full h-10">
                </div>

                <!-- Menu -->
                <button class="w-10 h-10 flex items-center justify-center text-[#6b7a90] hover:bg-gray-100 rounded">
                    ☰
                </button>
            </div>
        </header>

        <div class="flex">
            <!-- Sidebar -->
            <aside class="w-[265px] min-h-[calc(100vh-56px)] bg-[#3d4c68]" />

            <!-- Content -->
            <main class="flex-1 p-7">
                <!-- Breadcrumb -->
                <div class="flex items-center gap-3 text-[13px] text-gray-500 mb-5">
                    <span>TIỆN ÍCH</span>
                    <span>›</span>

                    <span class="font-semibold text-gray-700">TRA CỨU HÓA ĐƠN</span>
                </div>

                <!-- Card -->
                <div class="bg-white border border-gray-200">
                    <!-- Card Header -->
                    <div class="h-[52px] border-b border-gray-200 px-4 flex items-center justify-between">
                        <h2 class="font-semibold text-[14px] text-gray-700">
                            THÔNG TIN TÌM KIẾM
                        </h2>

                        <button class="text-gray-500 text-lg">⌄</button>
                    </div>

                    <!-- Card Body -->
                    <div class="p-8">
                        <!-- Row -->
                        <div class="grid grid-cols-2 gap-7">
                            <!-- MST -->
                            <div class="lg:grid grid-cols-12  gap-2">
                                <label
                                    class="block text-[14px] font-semibold mb-2 whitespace-nowrap col-span-4 text-end">
                                    MST bên bán
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class=" col-span-8">
                                    <input v-model="form.bill_sell_mst" type="text"
                                        class="w-full h-[34px] rounded-sm px-3 outline-none focus:ring-0" :class="displayError('bill_sell_mst')
                                            ? 'border border-red-500'
                                            : 'border border-gray-300 focus:border-blue-400'
                                            " @blur="markTouched('bill_sell_mst')" />

                                    <p v-if="displayError('bill_sell_mst')" class="mt-1 text-[13px] text-red-500">
                                        {{ displayError('bill_sell_mst') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="lg:grid grid-cols-12  gap-2">
                                <label
                                    class="block text-[14px] font-semibold mb-2 whitespace-nowrap col-span-4 text-end">
                                    Mã số bí mật
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class=" col-span-8">
                                    <input v-model="form.bill_private_key" type="text"
                                        class="w-full h-[34px] rounded-sm px-3 outline-none" :class="displayError('bill_private_key')
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
                        <div class="mt-7 flex justify-center">
                            <button type="button"
                                class="h-[34px] px-5 rounded bg-red-600 hover:bg-red-700 text-white text-[14px] font-medium flex items-center gap-2 disabled:opacity-60"
                                :disabled="isLoading" @click="submit">
                                <span class="text-[16px]">🔎</span>
                                <span>{{ isLoading ? 'Dang tim...' : 'Tìm kiếm' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="pdfPreviewUrl" class="mt-6 overflow-hidden rounded border border-gray-200 bg-white">
                    <div class="flex items-center justify-center gap-4 text-sm py-5">
                        <ul class="flex items-center justify-center gap-4 text-red-500 underline font-bold">
                            <li class=" flex items-center gap-2">
                                Tải về file PDF
                            </li>
                            <li class=" flex items-center gap-2">
                                Tải về file ZIp
                            </li>
                            <li class=" flex items-center gap-2">
                                Tải chứng thư số
                            </li>
                        </ul>
                        <button
                            class="bg-red-600 px-2 py-1 flex items-center justify-center gap-2 text-white rounded font-bold text-lg">
                            <PrinterIcon class="w-4 h-4" />
                            <span>In</span>
                        </button>
                    </div>
                    <object :data="pdfPreviewUrl" type="application/pdf" class="h-[760px] w-full">
                        <div class="p-4 text-sm text-gray-600">
                            Trinh duyet khong ho tro xem PDF truc tiep.
                            <a :href="pdfPreviewUrl" target="_blank" rel="noopener noreferrer"
                                class="text-blue-600 underline">
                                Bam vao day de mo file.
                            </a>
                        </div>
                    </object>
                </div>

                <!-- Footer -->
                <footer
                    class="mt-72 border-t border-gray-300 pt-6 flex items-center justify-between text-[12px] text-gray-500">
                    <div>
                        ©2016-2026 Bản quyền thuộc về Tập đoàn Công nghiệp - Viễn thông
                        Quân đội Viettel
                    </div>

                    <div>Hóa đơn điện tử SInvoice</div>
                </footer>
            </main>
        </div>
    </div>
</template>
