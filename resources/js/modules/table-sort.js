import { router } from '@inertiajs/react';

const UP_ICON = '▲';
const DOWN_ICON = '▼';

const getUrlParams = () => new URL(window.location.href).searchParams;

const getCurrentSort = () => {
    const params = getUrlParams();

    return {
        sort_by: params.get('sort_by') || null,
        sort_dir: params.get('sort_dir') === 'asc' ? 'asc' : 'desc',
    };
};

const setSortIndicators = (table) => {
    const { sort_by, sort_dir } = getCurrentSort();

    table.querySelectorAll('th[data-sort-index]').forEach((th) => {
        const indicator = th.querySelector('[data-sort-indicator]');
        if (!indicator) {
            return;
        }

        const index = th.dataset.sortIndex;
        if (!index) {
            indicator.textContent = '';
            return;
        }

        indicator.textContent = index === sort_by ? (sort_dir === 'desc' ? DOWN_ICON : UP_ICON) : '';
    });
};

const navigateSorting = (sortBy, sortDir) => {
    const url = new URL(window.location.href);
    const params = new URLSearchParams(url.search);

    params.set('sort_by', sortBy);
    params.set('sort_dir', sortDir);
    params.delete('page');

    const query = Object.fromEntries(params.entries());

    router.get(url.pathname, query, {
        preserveScroll: true,
        preserveState: true,
    });
};

const initSortableTable = (table) => {
    if (!(table instanceof HTMLTableElement)) {
        return;
    }

    // Always refresh indicators in case this is a new/updated page after Inertia navigation.
    setSortIndicators(table);

    if (table.dataset.sortableInitialized === '1') {
        return;
    }

    table.dataset.sortableInitialized = '1';

    table.querySelectorAll('th[data-sort-index]').forEach((th) => {
        th.style.cursor = 'pointer';

        th.addEventListener('click', () => {
            const sortBy = th.dataset.sortIndex;
            if (!sortBy) {
                return;
            }

            const { sort_by, sort_dir } = getCurrentSort();

            if (sort_by === sortBy) {
                if (sort_dir === 'asc') {
                    navigateSorting(sortBy, 'desc');
                } else {
                    // 3rd click clear sorting
                    const url = new URL(window.location.href);
                    const params = new URLSearchParams(url.search);

                    params.delete('sort_by');
                    params.delete('sort_dir');
                    params.delete('page');

                    router.get(url.pathname, Object.fromEntries(params.entries()), {
                        preserveScroll: true,
                        preserveState: true,
                    });
                    return;
                }
            } else {
                navigateSorting(sortBy, 'asc');
            }
        });
    });
};

export const refreshSortableTables = () => {
    document.querySelectorAll('table[data-sortable-table="true"]').forEach((table) => {
        initSortableTable(table);
    });
};

if (typeof window !== 'undefined') {
    window.refreshSortableTables = refreshSortableTables;
}
