const UP_ICON = '▲';
const DOWN_ICON = '▼';

const normalizeSortIndicators = (root = document) => {
    root.querySelectorAll('[data-sort-indicator]').forEach((indicator) => {
        if (indicator.querySelector('i')) {
            return;
        }
        const raw = (indicator.textContent || '').trim();
        if (raw === '<>') {
            indicator.innerHTML = '';
            return;
        }
        if (raw === '^') {
            indicator.textContent = UP_ICON;
            return;
        }
        if (raw === 'v') {
            indicator.textContent = DOWN_ICON;
        }
    });
};

const observeTable = (table, scheduleNormalize) => {
    if (!(table instanceof HTMLTableElement)) {
        return;
    }
    if (table.dataset.sortObserver === '1') {
        return;
    }
    table.dataset.sortObserver = '1';
    const observer = new MutationObserver(scheduleNormalize);
    observer.observe(table, {
        childList: true,
        subtree: true,
        characterData: true,
    });
};

export const initTableSortNormalization = () => {
    const scheduleNormalize = () =>
        window.requestAnimationFrame(() => normalizeSortIndicators(document));

    const attachObservers = () => {
        document.querySelectorAll('table').forEach((table) => observeTable(table, scheduleNormalize));
    };

    document.addEventListener('click', (event) => {
        if (event.target.closest('th[data-sort-index]')) {
            scheduleNormalize();
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        normalizeSortIndicators(document);
        attachObservers();
    });

    normalizeSortIndicators(document);
    attachObservers();
};
