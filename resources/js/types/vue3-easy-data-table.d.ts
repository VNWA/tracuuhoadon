declare module 'vue3-easy-data-table' {
    import type { DefineComponent } from 'vue';

    export type Header = {
        text: string;
        value: string;
        sortable?: boolean;
        fixed?: boolean;
        width?: number;
    };

    const EasyDataTable: DefineComponent;

    export default EasyDataTable;
}
