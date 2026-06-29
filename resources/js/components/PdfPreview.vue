<script setup lang="ts">
import type { PDFDocumentProxy, RenderTask } from 'pdfjs-dist';
import { nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue';

type DiagnosticStep = {
    step: string;
    status: 'ok' | 'error';
    message?: string;
};

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
const errorDetail = ref<string | null>(null);
const diagnosticSteps = ref<DiagnosticStep[]>([]);
const containerWidth = ref(0);

let pdfjsInitialized = false;
let pdfWorker: Worker | null = null;
let currentDoc: PDFDocumentProxy | null = null;
let renderToken = 0;
let activeRenderTasks: RenderTask[] = [];
let resizeTimer: ReturnType<typeof setTimeout> | undefined;

const formatError = (failure: unknown): string => {
    if (failure instanceof Error) {
        return failure.message;
    }

    return String(failure);
};

const recordStep = (step: string, status: 'ok' | 'error', message?: string): void => {
    diagnosticSteps.value.push({ step, status, message });
};

type PdfJsModule = typeof import('pdfjs-dist');

const ensureMapPolyfill = (): void => {
    const mapPrototype = Map.prototype as typeof Map.prototype & {
        getOrInsertComputed?: (key: unknown, callback: () => unknown) => unknown;
    };

    if (typeof mapPrototype.getOrInsertComputed === 'function') {
        return;
    }

    mapPrototype.getOrInsertComputed = function (key: unknown, callback: () => unknown): unknown {
        if (this.has(key)) {
            return this.get(key);
        }

        const value = callback();
        this.set(key, value);

        return value;
    };
};

const ensurePdfjs = async (): Promise<PdfJsModule> => {
    ensureMapPolyfill();

    const pdfjs = await import('pdfjs-dist');

    if (!pdfjsInitialized) {
        try {
            const WorkerConstructor = (await import('pdfjs-dist/build/pdf.worker.min.mjs?worker')).default;
            pdfWorker = new WorkerConstructor();
            pdfjs.GlobalWorkerOptions.workerPort = pdfWorker;
            recordStep('worker', 'ok', 'workerPort (bundled)');
        } catch {
            const workerModule = await import('pdfjs-dist/build/pdf.worker.min.mjs?url');

            pdfjs.GlobalWorkerOptions.workerSrc =
                typeof workerModule.default === 'string' ? workerModule.default : String(workerModule.default);
            recordStep('worker', 'ok', pdfjs.GlobalWorkerOptions.workerSrc);
        }

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
        void currentDoc.cleanup();
        currentDoc = null;
    }
};

const computePageScale = (pageWidth: number, width: number): number => {
    const fitScale = width / pageWidth;

    return fitScale * (props.scale / 1.5);
};

const loadPdf = async (src: string): Promise<PDFDocumentProxy> => {
    const { getDocument } = await ensurePdfjs();

    if (src.startsWith('blob:')) {
        recordStep('source', 'ok', 'blob');

        const response = await fetch(src);

        if (!response.ok) {
            throw new Error(`Blob fetch failed: HTTP ${response.status}`);
        }

        const contentType = response.headers.get('content-type');
        const data = await response.arrayBuffer();

        if (data.byteLength === 0) {
            throw new Error('Blob fetch returned empty data.');
        }

        if (contentType && !contentType.toLowerCase().includes('application/pdf')) {
            throw new Error(`Unexpected content-type: ${contentType}`);
        }

        recordStep('load', 'ok', `${data.byteLength} bytes`);

        const pdf = await getDocument({ data }).promise;
        recordStep('parse', 'ok', `${pdf.numPages} page(s)`);

        return pdf;
    }

    recordStep('source', 'ok', 'url');

    const pdf = await getDocument({ url: src }).promise;
    recordStep('load', 'ok', src.slice(0, 120));
    recordStep('parse', 'ok', `${pdf.numPages} page(s)`);

    return pdf;
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

    const baseViewport = page.getViewport({ scale: 1 });
    const pageScale = computePageScale(baseViewport.width, width);
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
        throw new Error('Canvas container is not available.');
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

    recordStep('render', 'ok', `${pdf.numPages} page(s)`);
};

const renderPdf = async (src: string, width: number): Promise<void> => {
    const token = ++renderToken;

    isLoading.value = true;
    error.value = null;
    errorDetail.value = null;
    diagnosticSteps.value = [];
    destroyPdf();

    try {
        const pdf = await loadPdf(src);

        if (token !== renderToken) {
            void pdf.cleanup();

            return;
        }

        currentDoc = pdf;
        isLoading.value = false;

        await nextTick();
        updateContainerWidth();

        const renderWidth = containerRef.value?.clientWidth ?? width;

        if (renderWidth <= 0) {
            throw new Error('Container width is 0px.');
        }

        await renderAllPages(pdf, renderWidth, token);
    } catch (failure) {
        if (token === renderToken) {
            const message = formatError(failure);

            recordStep('error', 'error', message);
            error.value = 'Khong the doc file PDF.';
            errorDetail.value = message;
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
    pdfWorker?.terminate();
    pdfWorker = null;
    pdfjsInitialized = false;
});
</script>

<template>
    <div class="w-full">
        <div v-if="isLoading" class="flex items-center justify-center py-16 text-sm text-gray-500">
            Dang tai PDF...
        </div>

        <div v-else-if="error" class="p-4 text-sm text-gray-600">
            {{ error }}
            <p v-if="errorDetail" class="mt-2 rounded bg-red-50 p-2 font-mono text-xs text-red-700">
                {{ errorDetail }}
            </p>
            <ul v-if="diagnosticSteps.length" class="mt-2 space-y-1 rounded bg-amber-50 p-2 font-mono text-xs text-amber-950">
                <li v-for="(item, index) in diagnosticSteps" :key="index">
                    {{ item.step }}: {{ item.status }}<span v-if="item.message"> — {{ item.message }}</span>
                </li>
            </ul>
            <a :href="src" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-blue-600 underline">
                Bam vao day de mo file PDF.
            </a>
        </div>

        <div v-show="!isLoading && !error" ref="containerRef" class="flex w-full flex-col" />
    </div>
</template>
