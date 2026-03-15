import './bootstrap';
import '../css/app.css';
import Alpine from 'alpinejs';
import { initNotificationPolling } from './modules/notifications';
import { createSidebarComponent, initSidebarBootAttributes } from './modules/sidebar';
import { initTableSortNormalization } from './modules/table-sort';

window.Alpine = Alpine;

const bodyDataset = document.body?.dataset ?? {};
const loanDetailDealTemplate =
    bodyDataset.crmLoanDetailDealUrl || window.__crmLoanDetailDealUrl || '/loans/detail/__DEAL__';
const loanDetailLoanTemplate =
    bodyDataset.crmLoanDetailLoanUrl || window.__crmLoanDetailLoanUrl || '/loans/detail/by-loan/__LOAN__';
const notificationsEndpoint =
    bodyDataset.crmNotificationsCountUrl || window.__crmNotificationsCountUrl || '/notifications/count';

const LIST_SCROLL_STATE_KEY = 'crm.list.scroll.state';

const isReloadNavigation = () => {
    const navEntries = performance.getEntriesByType?.('navigation') || [];
    const navType = navEntries[0]?.type;
    return navType === 'reload';
};

const getNearestTableWrapIndex = (element) => {
    const wraps = Array.from(document.querySelectorAll('.crm-table-wrap'));
    if (!wraps.length || !element) {
        return 0;
    }

    const elementTop = element.getBoundingClientRect().top;
    let nearestIndex = 0;
    let nearestDistance = Number.POSITIVE_INFINITY;

    wraps.forEach((wrap, index) => {
        const distance = Math.abs(wrap.getBoundingClientRect().top - elementTop);
        if (distance < nearestDistance) {
            nearestDistance = distance;
            nearestIndex = index;
        }
    });

    return nearestIndex;
};

const saveListScrollPosition = (targetUrl = window.location.href, options = {}) => {
    try {
        const currentUrl = new URL(window.location.href);
        const nextUrl = new URL(targetUrl, window.location.origin);
        if (currentUrl.pathname !== nextUrl.pathname) {
            return;
        }
        const mode = options.mode === 'table-top' ? 'table-top' : 'preserve';
        sessionStorage.setItem(
            LIST_SCROLL_STATE_KEY,
            JSON.stringify({
                mode,
                pathname: currentUrl.pathname,
                scrollY: window.scrollY || window.pageYOffset || 0,
                tableIndex: Number.isInteger(options.tableIndex) ? options.tableIndex : 0,
                timestamp: Date.now(),
            }),
        );
    } catch {
        // Ignore storage and URL parsing failures.
    }
};

const restoreListScrollPosition = () => {
    try {
        const clearPendingTableMask = () => {
            document.documentElement.setAttribute('data-pending-table-scroll', '0');
        };

        if (isReloadNavigation()) {
            sessionStorage.removeItem(LIST_SCROLL_STATE_KEY);
            clearPendingTableMask();
            return;
        }

        const rawState = sessionStorage.getItem(LIST_SCROLL_STATE_KEY);
        if (!rawState) {
            clearPendingTableMask();
            return;
        }
        const state = JSON.parse(rawState);
        const samePage = state?.pathname === window.location.pathname;
        const isFresh = Date.now() - Number(state?.timestamp || 0) < 15000;
        const mode = state?.mode === 'table-top' ? 'table-top' : 'preserve';
        const initialTargetY = Number(state?.scrollY ?? 0);

        if (!samePage || !isFresh || Number.isNaN(initialTargetY)) {
            sessionStorage.removeItem(LIST_SCROLL_STATE_KEY);
            clearPendingTableMask();
            return;
        }

        const resolveTargetY = () => {
            if (mode === 'table-top') {
                const wraps = Array.from(document.querySelectorAll('.crm-table-wrap'));
                if (!wraps.length) {
                    return 0;
                }
                const index = Number(state?.tableIndex ?? 0);
                const safeIndex = Number.isInteger(index) && index >= 0 && index < wraps.length ? index : 0;
                const headerOffset = 96;
                return Math.max(0, wraps[safeIndex].getBoundingClientRect().top + window.scrollY - headerOffset);
            }

            return initialTargetY;
        };

        if (mode === 'preserve') {
            const restoreOnce = () => {
                const targetY = resolveTargetY();
                window.scrollTo(0, targetY);
            };

            restoreOnce();
            window.addEventListener('DOMContentLoaded', restoreOnce, { once: true });
            window.addEventListener('load', restoreOnce, { once: true });
            window.setTimeout(() => {
                sessionStorage.removeItem(LIST_SCROLL_STATE_KEY);
                clearPendingTableMask();
            }, 120);
            return;
        }

        let attempts = 0;
        const maxAttempts = 14;
        const restore = () => {
            const targetY = resolveTargetY();
            window.scrollTo(0, targetY);
            attempts += 1;
            if (attempts < maxAttempts) {
                window.requestAnimationFrame(restore);
            } else {
                sessionStorage.removeItem(LIST_SCROLL_STATE_KEY);
                clearPendingTableMask();
            }
        };

        restore();
        window.addEventListener('DOMContentLoaded', restore, { once: true });
        window.addEventListener('load', restore, { once: true });
        window.setTimeout(clearPendingTableMask, 500);
    } catch {
        sessionStorage.removeItem(LIST_SCROLL_STATE_KEY);
        document.documentElement.setAttribute('data-pending-table-scroll', '0');
    }
};

window.tableListState = (config = {}) => ({
    loadingList: false,
    syncSortParams(base) {
        const currentUrl = new URL(window.location.href);
        ['sort_by', 'sort_dir', 'sort_type'].forEach((key) => {
            if (!base.searchParams.has(key) && currentUrl.searchParams.has(key)) {
                base.searchParams.set(key, currentUrl.searchParams.get(key) || '');
            }
        });
    },
    buildQuery(pageUrl = null) {
        const baseUrl = pageUrl ?? config.endpoint ?? window.location.href;
        const base = new URL(baseUrl, window.location.origin);
        const searchKey = config.searchKey ?? 'search';
        this.syncSortParams(base);

        if (searchKey) {
            const searchValue = this.searchTerm ?? '';
            if (searchValue) {
                base.searchParams.set(searchKey, searchValue);
            } else {
                base.searchParams.delete(searchKey);
            }
        }

        Object.entries(config.filters ?? {}).forEach(([stateKey, queryKey]) => {
            const value = this[stateKey] ?? '';
            if (value) {
                base.searchParams.set(queryKey, value);
            } else {
                base.searchParams.delete(queryKey);
            }
        });

        return base;
    },
    refreshList(pageUrl = null) {
        this.loadingList = true;
        const url = this.buildQuery(pageUrl);
        saveListScrollPosition(url.toString());
        window.location.replace(url.toString());
    },
    handleTableClick(event) {
        const link = event.target.closest('a[href]');
        if (!link) {
            return;
        }
        const href = link.getAttribute('href') || '';
        if (href.includes('page=')) {
            this.loadingList = true;
            saveListScrollPosition(link.href, {
                mode: 'table-top',
                tableIndex: getNearestTableWrapIndex(link),
            });
        }
    },
});

Alpine.data('loanPageState', (extraState = {}) => ({
    selectedDeal: null,
    loanDetailLoading: false,
    modal: {},
    tableSort: {},
    loanDetailCache: {},
    ...extraState,
    openModal(key) {
        this.modal[key] = true;
    },
    closeModal(key) {
        this.modal[key] = false;
    },
    isModalOpen(key) {
        return this.modal[key] === true;
    },
    async openLoanDetail(dealId, modalKey, loanId = null) {
        if (!dealId && !loanId) {
            return;
        }

        this.loanDetailLoading = true;
        this.selectedDeal = null;
        this.openModal(modalKey);

        try {
            const cacheKey = loanId ? `loan:${loanId}` : `deal:${dealId}`;
            if (this.loanDetailCache[cacheKey]) {
                this.selectedDeal = this.loanDetailCache[cacheKey];
                return;
            }

            const url = loanId
                ? loanDetailLoanTemplate.replace('__LOAN__', encodeURIComponent(String(loanId)))
                : loanDetailDealTemplate.replace('__DEAL__', encodeURIComponent(String(dealId)));

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
});

            if (!response.ok) {
                throw new Error(`Failed to load detail (${response.status})`);
            }

            const payload = await response.json();
            this.selectedDeal = payload?.data ?? null;
            if (this.selectedDeal) {
                this.loanDetailCache[cacheKey] = this.selectedDeal;
            }
        } catch {
            this.selectedDeal = null;
        } finally {
            this.loanDetailLoading = false;
        }
    },
    toggleTableSort(tableRef, columnIndex, type = 'string') {
        const current = this.tableSort[tableRef] ?? {};
        const direction = current.columnIndex === columnIndex && current.direction === 'asc' ? 'desc' : 'asc';
        this.tableSort[tableRef] = { columnIndex, direction, type };
        this.applyTableSort(tableRef);
    },
    tableSortIndicator(tableRef, columnIndex) {
        const current = this.tableSort[tableRef];
        if (!current || current.columnIndex !== columnIndex) {
            return '';
        }
        return current.direction === 'asc' ? '▲' : '▼';
    },
    applyTableSort(tableRef) {
        const state = this.tableSort[tableRef];
        const table = this.$refs?.[tableRef];
        if (!state || !table) {
            return;
        }

        const tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }

        const rows = [...tbody.querySelectorAll('tr[data-sortable-row]')];
        const { columnIndex, direction, type } = state;
        const factor = direction === 'asc' ? 1 : -1;

        rows.sort((a, b) => {
            const aCell = a.children[columnIndex];
            const bCell = b.children[columnIndex];
            const aRaw = (aCell?.dataset.sortValue ?? aCell?.innerText ?? '').trim();
            const bRaw = (bCell?.dataset.sortValue ?? bCell?.innerText ?? '').trim();
            const aEmpty = aRaw === '' || aRaw === '-' || aRaw.toLowerCase() === 'null';
            const bEmpty = bRaw === '' || bRaw === '-' || bRaw.toLowerCase() === 'null';

            if (aEmpty !== bEmpty) {
                return aEmpty ? -1 : 1;
            }

            if (type === 'number') {
                return ((Number(aRaw) || 0) - (Number(bRaw) || 0)) * factor;
            }
            if (type === 'date') {
                return ((Date.parse(aRaw) || 0) - (Date.parse(bRaw) || 0)) * factor;
            }

            return aRaw.localeCompare(bRaw, undefined, { numeric: true, sensitivity: 'base' }) * factor;
        });

        rows.forEach((row) => tbody.appendChild(row));
    },
}));

const enableDeleteConfirmDialog = () => {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        if (!form.hasAttribute('data-confirm')) {
            return;
        }
        const message = form.getAttribute('data-confirm') || 'Are you sure you want to continue?';
        const confirmed = window.confirm(message);
        if (!confirmed) {
            event.preventDefault();
        }
    });
};

const enableSortableTables = () => {
    const pageSortBy = new URL(window.location.href).searchParams.get('sort_by');
    const pageSortDirection = new URL(window.location.href).searchParams.get('sort_dir');

    const syncSortUrl = (sortBy, direction, type) => {
        const sortUrl = new URL(window.location.href);
        sortUrl.searchParams.set('sort_by', String(sortBy));
        sortUrl.searchParams.set('sort_dir', direction);
        sortUrl.searchParams.set('sort_type', type);
        sortUrl.searchParams.delete('page');
        saveListScrollPosition(sortUrl.toString());
        window.location.replace(`${sortUrl.pathname}${sortUrl.search ? `?${sortUrl.searchParams.toString()}` : ''}`);
    };

    const clearSortUrl = () => {
        const sortUrl = new URL(window.location.href);
        sortUrl.searchParams.delete('sort_by');
        sortUrl.searchParams.delete('sort_dir');
        sortUrl.searchParams.delete('sort_type');
        sortUrl.searchParams.delete('page');
        saveListScrollPosition(sortUrl.toString());
        window.location.replace(`${sortUrl.pathname}${sortUrl.search ? `?${sortUrl.searchParams.toString()}` : ''}`);
    };

    document.querySelectorAll('table[data-sortable-table="true"]').forEach((table) => {
        table.querySelectorAll('thead th[data-sort-index]').forEach((header) => {
            if (header.dataset.sortBound === '1') {
                return;
            }
            header.dataset.sortBound = '1';
            header.classList.add('cursor-pointer');
            const indicator = header.querySelector('[data-sort-indicator]');
            if (indicator) {
                indicator.innerHTML = '';
            }
            header.addEventListener('click', () => {
                const index = Number(header.dataset.sortIndex);
                const type = header.dataset.sortType || 'string';
                const isCurrent = String(index) === String(pageSortBy);
                if (isCurrent && pageSortDirection === 'desc') {
                    clearSortUrl();
                    return;
                }
                const currentDirection = isCurrent && pageSortDirection === 'asc' ? 'desc' : 'asc';
                header.dataset.direction = currentDirection;
                syncSortUrl(index, currentDirection, type);
            });
        });

        if (pageSortBy && (pageSortDirection === 'asc' || pageSortDirection === 'desc')) {
            const activeHeader = table.querySelector(`thead th[data-sort-index="${pageSortBy}"]`);
            if (activeHeader) {
                activeHeader.dataset.direction = pageSortDirection;
                const indicator = activeHeader.querySelector('[data-sort-indicator]');
                if (indicator) {
                    indicator.textContent = pageSortDirection === 'asc' ? '▲' : '▼';
                }
            }
        }
    });
};

const enablePaginationTableTopRestore = () => {
    document.addEventListener(
        'click',
        (event) => {
            const link = event.target.closest('a[href]');
            if (!link) {
                return;
            }
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            const href = link.getAttribute('href') || '';
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
                return;
            }

            const nextUrl = new URL(href, window.location.origin);
            if (nextUrl.origin !== window.location.origin || !nextUrl.searchParams.has('page')) {
                return;
            }

            saveListScrollPosition(nextUrl.toString(), {
                mode: 'table-top',
                tableIndex: getNearestTableWrapIndex(link),
            });
        },
        true,
    );
};

const enableSearchableNameSelects = () => {
    const toInitials = (text) => {
        const words = String(text || '')
            .trim()
            .split(/\s+/)
            .filter(Boolean);
        if (!words.length) {
            return '?';
        }
        if (words.length === 1) {
            return words[0].slice(0, 2).toUpperCase();
        }
        return (words[0][0] + words[1][0]).toUpperCase();
    };

    document.querySelectorAll('select.crm-form-select, select[data-searchable-name="true"], select[data-enhanced-select="true"]').forEach((select) => {
        if (!(select instanceof HTMLSelectElement) || select.dataset.searchBound === '1') {
            return;
        }
        if (select.dataset.nativeSelect === 'true') {
            return;
        }

        select.dataset.searchBound = '1';
        const isSearchable = select.dataset.searchableName === 'true';

        const placeholder = select.dataset.searchPlaceholder || 'Search...';
        const emptyOption = Array.from(select.options).find((option) => option.value === '');
        const triggerPlaceholder = emptyOption?.textContent?.trim() || 'Select option';

        const wrapper = document.createElement('div');
        wrapper.className = 'crm-search-select';

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'crm-search-select-trigger';
        trigger.innerHTML = `
            <span class="crm-search-select-trigger-label">${triggerPlaceholder}</span>
            <span class="crm-search-select-trigger-caret">&#9662;</span>
        `;

        const panel = document.createElement('div');
        panel.className = 'crm-search-select-panel';
        panel.hidden = true;

        const optionsList = document.createElement('div');
        optionsList.className = 'crm-search-select-options';
        let searchInput = null;
        if (isSearchable) {
            const searchWrap = document.createElement('div');
            searchWrap.className = 'crm-search-select-search-wrap';
            const searchIcon = document.createElement('span');
            searchIcon.className = 'crm-search-select-search-icon';
            searchIcon.textContent = '🔍';
            searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.autocomplete = 'off';
            searchInput.placeholder = placeholder;
            searchInput.className = 'crm-search-select-search-input';
            searchWrap.append(searchIcon, searchInput);
            panel.append(searchWrap);
        }
        panel.append(optionsList);

        select.parentNode?.insertBefore(wrapper, select);
        wrapper.append(trigger, select);
        document.body.appendChild(panel);
        select.classList.add('hidden');

        let open = false;
        let lastValue = select.value;
        let unbindFloatingHandlers = null;

        const placePanel = () => {
            const rect = trigger.getBoundingClientRect();
            panel.style.position = 'fixed';
            panel.style.left = `${rect.left}px`;
            panel.style.top = `${rect.bottom + 6}px`;
            panel.style.width = `${rect.width}px`;
            panel.style.zIndex = '90';
        };

        const setOpen = (nextOpen) => {
            open = nextOpen;
            panel.hidden = !open;
            wrapper.classList.toggle('is-open', open);
            if (open) {
                if (searchInput) {
                    searchInput.value = '';
                }
                renderOptions();
                placePanel();
                const onViewportChange = () => placePanel();
                window.addEventListener('resize', onViewportChange);
                window.addEventListener('scroll', onViewportChange, true);
                unbindFloatingHandlers = () => {
                    window.removeEventListener('resize', onViewportChange);
                    window.removeEventListener('scroll', onViewportChange, true);
                };
                if (searchInput) {
                    window.requestAnimationFrame(() => searchInput.focus());
                }
            } else if (unbindFloatingHandlers) {
                unbindFloatingHandlers();
                unbindFloatingHandlers = null;
            }
        };

        const syncTriggerLabel = () => {
            const selectedOption = Array.from(select.options).find((option) => option.value === select.value);
            const label = selectedOption?.textContent?.trim() || triggerPlaceholder;
            const labelNode = trigger.querySelector('.crm-search-select-trigger-label');
            if (labelNode) {
                labelNode.textContent = label;
            }
        };

        const renderOptions = () => {
            const keyword = searchInput ? searchInput.value.trim().toLowerCase() : '';
            const candidates = Array.from(select.options).filter((option) => option.value !== '');
            const filtered = candidates.filter((option) => option.textContent.toLowerCase().includes(keyword));

            optionsList.innerHTML = '';
            if (!filtered.length) {
                const empty = document.createElement('div');
                empty.className = 'crm-search-select-empty';
                empty.textContent = 'No results';
                optionsList.appendChild(empty);
                return;
            }

            filtered.forEach((option) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'crm-search-select-option';
                if (option.value === select.value) {
                    item.classList.add('is-selected');
                }
                if (isSearchable) {
                    item.innerHTML = `
                        <span class="crm-search-select-avatar">${toInitials(option.textContent)}</span>
                        <span class="crm-search-select-option-text">${option.textContent}</span>
                    `;
                } else {
                    item.innerHTML = `<span class="crm-search-select-option-text">${option.textContent}</span>`;
                }
                item.addEventListener('click', () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    select.dispatchEvent(new Event('input', { bubbles: true }));
                    syncTriggerLabel();
                    setOpen(false);
                });
                optionsList.appendChild(item);
            });
        };

        const syncDisabledState = () => {
            const isDisabled = select.disabled;
            trigger.disabled = isDisabled;
            wrapper.classList.toggle('is-disabled', isDisabled);
        };

        trigger.addEventListener('click', () => {
            if (trigger.disabled) {
                return;
            }
            setOpen(!open);
        });

        if (searchInput) {
            searchInput.addEventListener('input', renderOptions);
        }
        select.addEventListener('change', () => {
            syncTriggerLabel();
            if (open) {
                renderOptions();
            }
        });

        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target) && !panel.contains(event.target)) {
                setOpen(false);
            }
        });

        syncTriggerLabel();
        syncDisabledState();

        const observer = new MutationObserver(syncDisabledState);
        observer.observe(select, { attributes: true, attributeFilter: ['disabled'] });

        window.setInterval(() => {
            if (select.value !== lastValue) {
                lastValue = select.value;
                syncTriggerLabel();
                if (open) {
                    renderOptions();
                }
            }
            syncDisabledState();
        }, 200);
    });
};

const enableFormListStatePreserve = () => {
    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            if (!form.hasAttribute('data-preserve-list-state')) {
                return;
            }
            saveListScrollPosition(window.location.href);
        },
        true,
    );
};

Alpine.data('crmLayout', () => createSidebarComponent());
initSidebarBootAttributes();
Alpine.start();
restoreListScrollPosition();

enableDeleteConfirmDialog();
enableSortableTables();
enablePaginationTableTopRestore();
enableSearchableNameSelects();
enableFormListStatePreserve();
initTableSortNormalization();
initNotificationPolling({ endpoint: notificationsEndpoint });
window.refreshSortableTables = enableSortableTables;
