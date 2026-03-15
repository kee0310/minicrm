import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';

type FilterValue = string | number | boolean | null | undefined;

type FilterParams = Record<string, FilterValue>;

type UseCrmFiltersOptions = {
    baseUrl: string;
    searchKey?: string;
    statusKey?: string;
    defaults?: {
        search?: string;
        status?: string;
    };
    preserveScroll?: boolean;
    preserveState?: boolean;
    extra?: FilterParams;
};

type UseCrmFiltersReturn = {
    searchTerm: string;
    setSearchTerm: (value: string) => void;
    statusFilter: string;
    setStatusFilter: (value: string) => void;
    submitSearch: (event?: React.FormEvent, overrides?: FilterParams) => void;
    resetSearch: () => void;
    applyStatus: (status: string, overrides?: FilterParams) => void;
    buildQuery: (overrides?: FilterParams) => FilterParams;
};

const normalizeQueryValue = (value: FilterValue): string | undefined => {
    if (value === undefined || value === null || value === '') {
        return undefined;
    }
    return String(value);
};

export function useCrmFilters({
    baseUrl,
    searchKey = 'search',
    statusKey = 'status',
    defaults,
    preserveScroll = true,
    preserveState = true,
    extra,
}: UseCrmFiltersOptions): UseCrmFiltersReturn {
    const page = usePage();

    const params = useMemo(() => {
        const origin =
            typeof window === 'undefined' ? 'http://localhost' : window.location.origin;
        return new URL(`${origin}${page.url}`).searchParams;
    }, [page.url]);

    const [searchTerm, setSearchTerm] = useState<string>(
        params.get(searchKey) ?? defaults?.search ?? '',
    );
    const [statusFilter, setStatusFilter] = useState<string>(
        params.get(statusKey) ?? defaults?.status ?? '',
    );

    const syncFromUrl = useCallback(() => {
        if (typeof window === 'undefined') {
            return;
        }
        const searchParams = new URL(window.location.href).searchParams;
        const nextSearch = searchParams.get(searchKey) ?? defaults?.search ?? '';
        const nextStatus = searchParams.get(statusKey) ?? defaults?.status ?? '';
        setSearchTerm(nextSearch);
        setStatusFilter(nextStatus);
    }, [searchKey, statusKey, defaults?.search, defaults?.status]);

    useEffect(() => {
        const removeNavigate = router.on('navigate', syncFromUrl);
        const removeFinish = router.on('finish', syncFromUrl);

        return () => {
            removeNavigate();
            removeFinish();
        };
    }, [syncFromUrl]);

    const buildQuery = useCallback(
        (overrides?: FilterParams) => {
            const payload: FilterParams = { ...extra, ...(overrides ?? {}) };
            const nextSearch =
                overrides?.[searchKey] ?? overrides?.search ?? searchTerm;
            const nextStatus =
                overrides?.[statusKey] ?? overrides?.status ?? statusFilter;

            const resolvedSearch = normalizeQueryValue(nextSearch);
            if (resolvedSearch) {
                payload[searchKey] = resolvedSearch;
            } else {
                delete payload[searchKey];
            }

            const resolvedStatus = normalizeQueryValue(nextStatus);
            if (resolvedStatus) {
                payload[statusKey] = resolvedStatus;
            } else {
                delete payload[statusKey];
            }

            Object.keys(payload).forEach((key) => {
                const resolved = normalizeQueryValue(payload[key]);
                if (resolved === undefined) {
                    delete payload[key];
                } else {
                    payload[key] = resolved;
                }
            });

            return payload;
        },
        [extra, searchKey, statusKey, searchTerm, statusFilter],
    );

    const navigate = useCallback(
        (overrides?: FilterParams) => {
            router.get(baseUrl, buildQuery(overrides), {
                preserveScroll,
                preserveState,
            });
        },
        [baseUrl, buildQuery, preserveScroll, preserveState],
    );

    const submitSearch = useCallback(
        (event?: React.FormEvent, overrides?: FilterParams) => {
            if (event) {
                event.preventDefault();
            }
            navigate(overrides);
        },
        [navigate],
    );

    const resetSearch = useCallback(() => {
        setSearchTerm('');
        navigate({ [searchKey]: '' });
    }, [navigate, searchKey]);

    const applyStatus = useCallback(
        (status: string, overrides?: FilterParams) => {
            setStatusFilter(status);
            navigate({ ...overrides, [statusKey]: status });
        },
        [navigate, statusKey],
    );

    return {
        searchTerm,
        setSearchTerm,
        statusFilter,
        setStatusFilter,
        submitSearch,
        resetSearch,
        applyStatus,
        buildQuery,
    };
}
