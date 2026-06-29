<script setup lang="ts">
import type { PDFDocumentProxy, PDFPageProxy, RenderTask } from 'pdfjs-dist';
import { nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        src: string;
        scale?: number;
    }>(),
    {
        scale: 1.5,
    },
);

const containerRef = useTemplateRef<HTMLDivElement>('containerRef');

const isLoading = ref(false);
const error = ref<string | null>(null);
const containerWidth = ref(0);

let pdfjsInitialized = false;
let currentDoc: PDFDocumentProxy | null = null;
let renderToken = 0;
let activeRenderTasks: RenderTask[] = [];
let resizeTimer: ReturnType<typeof setTimeout> | undefined;

const ensurePdfjs = async (): Promise<typeof import('pdfjs-dist')> => {
    const pdfjs = await import('pdfjs-dist');

    if (!pdfjsInitialized) {
        const pdfjsWorker = await import('pdfjs-dist/build/pdf.worker.min.mjs?url');
        pdfjs.GlobalWorkerOptions.workerSrc = pdfjsWorker.default;
        pdfjsInitialized = true;
    }

    return pdfjs;
};

const updateContainerWidth = (): void => {
    const width = containerRef.value?.clientWidth ?? 0;

    if (width > 0) {
        containerWidth.value = width;
    }
};

const cancelActiveRenders = (): void => {
    for (const task of activeRenderTasks) {
        task.cancel();
    }

    activeRenderTasks = [];
};

const clearCanvases = (): void => {
    containerRef.value?.replaceChildren();
};

const destroyPdf = (): void => {
    cancelActiveRenders();
    clearCanvases();

    if (currentDoc) {
        void currentDoc.destroy();
        currentDoc = null;
    }
};

const computePageScale = (page: PDFPageProxy, width: number): number => {
    const baseViewport = page.getViewport({ scale: 1 });
    const fitScale = width / baseViewport.width;

    return fitScale * (props.scale / 1.5);
};

const loadPdf = async (src: string): Promise<PDFDocumentProxy> => {
    const { getDocument } = await ensurePdfjs();
    const loadingTask = getDocument({ url: src });

    return loadingTask.promise;
};

const renderPage = async (
    pdf: PDFDocumentProxy,
    pageNumber: number,
    canvas: HTMLCanvasElement,
    width: number,
    token: number,
): Promise<void> => {
    const page = await pdf.getPage(pageNumber);

    if (token !== renderToken) {
        return;
    }

    const context = canvas.getContext('2d');

    if (!context) {
        throw new Error('Canvas context unavailable');
    }

    const pageScale = computePageScale(page, width);
    const devicePixelRatio = window.devicePixelRatio || 1;
    const viewport = page.getViewport({ scale: pageScale * devicePixelRatio });

    canvas.width = viewport.width;
    canvas.height = viewport.height;
    canvas.style.width = '100%';
    canvas.style.height = 'auto';

    const renderTask = page.render({
        canvas,
        viewport,
    });

    activeRenderTasks.push(renderTask);

    try {
        await renderTask.promise;
    } finally {
        activeRenderTasks = activeRenderTasks.filter((task) => task !== renderTask);
    }
};

const renderAllPages = async (pdf: PDFDocumentProxy, width: number, token: number): Promise<void> => {
    const container = containerRef.value;

    if (!container) {
        return;
    }

    clearCanvases();

    for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
        if (token !== renderToken) {
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.className = 'block w-full h-auto';
        container.appendChild(canvas);

        await renderPage(pdf, pageNumber, canvas, width, token);
    }
};

const renderPdf = async (src: string, width: number): Promise<void> => {
    const token = ++renderToken;

    isLoading.value = true;
    error.value = null;
    destroyPdf();

    try {
        const pdf = await loadPdf(src);

        if (token !== renderToken) {
            void pdf.destroy();

            return;
        }

        currentDoc = pdf;
        isLoading.value = false;

        await nextTick();
        updateContainerWidth();

        const renderWidth = containerRef.value?.clientWidth ?? width;

        if (renderWidth <= 0) {
            return;
        }

        await renderAllPages(pdf, renderWidth, token);
    } catch {
        if (token === renderToken) {
            error.value = 'Khong the doc file PDF.';
        }
    } finally {
        if (token === renderToken) {
            isLoading.value = false;
        }
    }
};

const scheduleRender = (): void => {
    if (import.meta.env.SSR || !props.src) {
        return;
    }

    updateContainerWidth();

    const width = containerWidth.value || containerRef.value?.clientWidth || 0;

    if (width <= 0) {
        return;
    }

    void renderPdf(props.src, width);
};

const onResize = (): void => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(updateContainerWidth, 150);
};

watch(
    () => props.src,
    () => scheduleRender(),
);

watch(
    () => props.scale,
    () => {
        if (currentDoc && containerWidth.value > 0) {
            void renderAllPages(currentDoc, containerWidth.value, renderToken);
        }
    },
);

watch(containerWidth, (width, previousWidth) => {
    if (width <= 0 || !currentDoc || isLoading.value) {
        return;
    }

    if (previousWidth <= 0 || Math.abs(width - previousWidth) > 8) {
        void renderAllPages(currentDoc, width, renderToken);
    }
});

onMounted(() => {
    updateContainerWidth();
    window.addEventListener('resize', onResize);
    scheduleRender();
});

onBeforeUnmount(() => {
    renderToken++;
    clearTimeout(resizeTimer);
    window.removeEventListener('resize', onResize);
    destroyPdf();
});
</script>

<template>
    <div class="w-full">
        <div v-if="isLoading" class="flex items-center justify-center py-16 text-sm text-gray-500">
            Dang tai PDF...
        </div>

        <div v-else-if="error" class="p-4 text-sm text-gray-600">
            {{ error }}
            <a :href="src" target="_blank" rel="noopener noreferrer" class="text-blue-600 underline">
                Bam vao day de mo file PDF.
            </a>
        </div>

        <div v-show="!isLoading && !error" ref="containerRef" class="flex w-full flex-col" />
    </div>
</template>
