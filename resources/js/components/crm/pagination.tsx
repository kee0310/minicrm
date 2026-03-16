import { router } from '@inertiajs/react';

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginationProps = {
    links: PaginationLink[];
    onNavigate?: (url: string) => void;
};

export function CrmPagination({ links, onNavigate }: PaginationProps) {
    if (!links || links.length === 0) {
        return null;
    }

    const getNearestTableWrapIndex = (element?: Element | null) => {
        const wraps = Array.from(document.querySelectorAll('.crm-table-wrap'));
        if (!wraps.length || !element) {
            return 0;
        }

        const elementTop = element.getBoundingClientRect().top;
        let nearestIndex = 0;
        let nearestDistance = Number.POSITIVE_INFINITY;

        wraps.forEach((wrap, index) => {
            const distance = Math.abs(
                wrap.getBoundingClientRect().top - elementTop,
            );
            if (distance < nearestDistance) {
                nearestDistance = distance;
                nearestIndex = index;
            }
        });

        return nearestIndex;
    };

    const scrollToTableTop = (tableIndex = 0) => {
        const wraps = Array.from(document.querySelectorAll('.crm-table-wrap'));
        if (!wraps.length) {
            window.scrollTo(0, 0);
            return;
        }
        const safeIndex =
            tableIndex >= 0 && tableIndex < wraps.length ? tableIndex : 0;
        const target = wraps[safeIndex];
        if (!target) {
            return;
        }
        const headerOffset = 96;
        const targetY =
            target.getBoundingClientRect().top + window.scrollY - headerOffset;
        window.scrollTo({ top: Math.max(0, targetY) });
    };

    const handleNavigate = (url: string, element?: Element | null) => {
        if (onNavigate) {
            onNavigate(url);
            return;
        }
        const tableIndex = getNearestTableWrapIndex(element);
        router.get(
            url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    window.requestAnimationFrame(() => {
                        scrollToTableTop(tableIndex);
                    });
                },
            },
        );
    };

    return (
        <div className="flex w-full flex-wrap justify-end gap-1">
            {links.map((link) => (
                <button
                    key={link.label}
                    type="button"
                    disabled={!link.url || link.active}
                    onClick={(event) =>
                        link.url &&
                        handleNavigate(link.url, event.currentTarget)
                    }
                    className={`rounded border px-3 py-1 text-sm ${
                        link.active
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-slate-200 bg-white text-slate-700'
                    } ${!link.url || link.active ? 'cursor-default' : 'hover:bg-slate-100'}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                ></button>
            ))}
        </div>
    );
}
