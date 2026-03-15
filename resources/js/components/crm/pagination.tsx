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

    const handleNavigate = (url: string) => {
        if (onNavigate) {
            onNavigate(url);
            return;
        }
        router.get(url);
    };

    return (
        <div className="flex flex-wrap gap-1">
            {links.map((link) => (
                <button
                    key={link.label}
                    type="button"
                    disabled={!link.url}
                    onClick={() => link.url && handleNavigate(link.url)}
                    className={`px-3 py-1 text-sm rounded border ${
                        link.active
                            ? 'bg-slate-900 text-white border-slate-900'
                            : 'bg-white text-slate-700 border-slate-200'
                    } ${!link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-100'}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                ></button>
            ))}
        </div>
    );
}
