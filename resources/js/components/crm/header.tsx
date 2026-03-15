import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { logout } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';
import type { CrmShared } from '@/types/crm';

type Props = {
    title?: string | ReactNode;
    subtitle?: string;
    onToggleMobile: () => void;
    crm: CrmShared;
    user: {
        name: string;
        email: string;
    };
};

const SUBTITLE_MAP: Record<string, string> = {
    'dashboard.index': 'Executive and operational overview',
    'leads.index': 'Track and manage every incoming lead',
    'deals.index': 'Monitor deal progress across the pipeline',
    'clients.index': 'Manage client records and relationships',
    'clients.show': 'View client details and related deals',
    'users.index': 'Control user access and team roles',
    'commissions.index': 'Review commission eligibility and payouts',
    'legals.index': 'Track legal progress and milestones',
    'profile.edit': 'Manage your account settings and security',
    'loans.pre-qualification': 'Assess loan readiness before submission',
    'loans.borrower-profile': 'Review borrower credit and financial profile',
    'loans.bank-submission-tracking':
        'Track loan submissions and approval status',
    'loans.approval-analysis': 'Compare approved terms across applications',
    'loans.disbursement': 'Track post-approval disbursement milestones',
};

export function CrmHeader({ title, subtitle, onToggleMobile, crm, user }: Props) {
    const fallbackTitle = (
        <h2 className="text-lg font-semibold text-slate-900">
            CRM
        </h2>
    );
    const resolvedSubtitle =
        subtitle ??
        (crm.routeName ? SUBTITLE_MAP[crm.routeName] : '') ??
        'Manage and track your CRM operations';

    const initials = user?.name ? user.name.slice(0, 1).toUpperCase() : 'U';

    return (
        <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div className="crm-content py-4">
                <div className="flex items-center justify-between gap-4">
                    <div className="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            className="crm-btn-secondary px-3 py-2 lg:hidden"
                            onClick={onToggleMobile}
                        >
                            <span>Menu</span>
                        </button>
                        <div className="min-w-0">
                            {title ?? fallbackTitle}
                            {resolvedSubtitle ? (
                                <p className="mt-1 text-sm text-slate-500">
                                    {resolvedSubtitle}
                                </p>
                            ) : null}
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="text-right">
                            <p className="text-sm font-semibold text-slate-900">
                                {user?.name}
                            </p>
                            <p className="text-xs text-slate-500">
                                {user?.email}
                            </p>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 hover:bg-slate-50">
                                    {initials}
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-48" align="end">
                                <DropdownMenuItem asChild>
                                    <Link href={profileEdit().url}>
                                        Profile
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={logout().url}
                                        method="post"
                                        as="button"
                                    >
                                        Log Out
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>
        </header>
    );
}
