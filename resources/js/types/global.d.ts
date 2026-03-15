export {};

declare global {
    interface Window {
        refreshSortableTables?: () => void;
    }
}

declare module 'chart.js/auto';
