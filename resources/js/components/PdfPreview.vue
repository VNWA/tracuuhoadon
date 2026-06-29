<script setup lang="ts">
import { getDocument, GlobalWorkerOptions, type PDFDocumentProxy } from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
import { useElementSize } from '@vueuse/core';
import { onBeforeUnmount, ref, watch } from 'vue';

GlobalWorkerOptions.workerSrc = pdfjsWorker;

const props = defineProps<{
    src: string;
}>();

const containerRef = ref<HTMLElement | null>(null);
const { width: containerWidth } = useElementSize(containerRef);

const isLoading = ref(false);
const error = ref<string | null>(null);
const pageImages = ref<string[]>([]);

let currentDoc: PDFDocumentProxy | null = null;
let renderToken = 0;

const destroyCurrentDoc = (): void => {
    if (currentDoc) {
        void currentDoc.cleanup();
        currentDoc = null;
    }
};

const renderPdf = async (src: string, width: number): Promise<void> => {
    const token = ++renderToken;
    isLoading.value = true;
    error.value = null;
    pageImages.value = [];

    try {
        const loadingTask = getDocument({ url: src });
        const pdf = await loadingTask.promise;

        if (token !== renderToken) {
            void pdf.cleanup();

            return;
        }

        destroyCurrentDoc();
        currentDoc = pdf;

        const images: string[] = [];
        const dpr = window.devicePixelRatio || 1;
        const targetWidth = width * dpr;

        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
            const page = await pdf.getPage(pageNum);

            if (token !== renderToken) {
                return;
            }

            const baseViewport = page.getViewport({ scale: 1 });
            const scale = targetWidth / baseViewport.width;
            const viewport = page.getViewport({ scale });

            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;

            const context = canvas.getContext('2d');

            if (!context) {
                throw new Error('Canvas context unavailable');
            }

            await page.render({
                canvas,
                viewport,
            }).promise;

            images.push(canvas.toDataURL('image/png'));
        }

        if (token === renderToken) {
            pageImages.value = images;
        }
    } catch {
        if (token === renderToken) {
            error.value = 'Khong the hien thi file PDF.';
        }
    } finally {
        if (token === renderToken) {
            isLoading.value = false;
        }
    }
};

watch(
    [() => props.src, containerWidth],
    ([src, width]) => {
        if (!src || width <= 0) {
            return;
        }

        renderPdf(src, width);
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    renderToken++;
    destroyCurrentDoc();
});
</script>

<template>
    <div ref="containerRef" class="w-full">
        <div v-if="isLoading" class="flex items-center justify-center py-16 text-sm text-gray-500">
            Dang tai hoa don...
        </div>

        <div v-else-if="error" class="p-4 text-sm text-gray-600">
            {{ error }}
            <a :href="src" target="_blank" rel="noopener noreferrer" class="text-blue-600 underline">
                Bam vao day de mo file PDF.
            </a>
        </div>

        <div v-else class="flex flex-col">
            <img
                v-for="(image, index) in pageImages"
                :key="index"
                :src="image"
                alt=""
                class="block w-full h-auto"
            />
        </div>
    </div>
</template>
