
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState, type ReactNode } from 'react';
import { CrmFilterSearch } from '@/components/crm/filter-search';
import { CrmFilterTabs } from '@/components/crm/filter-tabs';
import { CrmPagination, type PaginationLink } from '@/components/crm/pagination';
import { useCrmFilters } from '@/hooks/use-crm-filters';
import CrmLayout from '@/layouts/crm-layout';

type PerformanceRow = {
    name?: string;
    leader?: string;
    team_size?: number;
    converted_leads?: number;
    completed_deals?: number;
    close_rate?: number;
    avg_complete_days?: number | null;
    total_commission?: number;
};

type PaginatedRows = {
    data: PerformanceRow[];
    links: PaginationLink[];
};

type SalespeopleProps = {
    pageTitle?: string;
    pageSubtitle?: string;
    activeTab?: 'salesperson' | 'leader';
    rows: PaginatedRows;
    selectedMonth?: string;
};

function formatNumber(value: number | null | undefined, decimals = 0) {
    if (value === null || value === undefined) {
        return '-';
    }
    return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

export default function Salespeople(props: SalespeopleProps) {
    const [monthFilter, setMonthFilter] = useState(props.selectedMonth ?? '');
    const {
        searchTerm,
        setSearchTerm,
        statusFilter: tab,
        submitSearch,
        resetSearch,
        applyStatus,
    } = useCrmFilters({
        baseUrl: '/dashboard/salespeople',
        statusKey: 'tab',
        defaults: { status: props.activeTab ?? 'salesperson' },
        extra: { month: monthFilter },
    });

    const isLeaderTab = tab === 'leader';

    const rows = props.rows?.data ?? [];

    const listTitle = isLeaderTab ? 'List of leaders' : 'List of salesperson';
    const emptyMessage = isLeaderTab
        ? 'No leader data available.'
        : 'No salesperson data available.';
    const searchPlaceholder = isLeaderTab
        ? 'Search leader...'
        : 'Search salesperson or leader...';

    const monthLabel = useMemo(() => {
        if (!monthFilter) {
            return 'Select month';
        }
        const [year, month] = monthFilter.split('-');
        if (!year || !month) {
            return monthFilter;
        }
        const monthIndex = Number(month) - 1;
        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const label = labels[monthIndex] ?? month;
        return `${label} ${year}`;
    }, [monthFilter]);

    return (
        <>
            <Head title="Salesperson Performance" />
            <Link
                href="/dashboard"
                className="ml-4 inline-flex items-center gap-2 border border-slate-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 shadow-sm transition hover:bg-blue-500 hover:text-white"
            >
                <span aria-hidden="true">&larr;</span>
                Back
            </Link>

            <div className="space-y-6">
                <div className="crm-card">
                    <div className="crm-card-body text-gray-900">
                        <div className="flex items-center justify-between gap-3 mb-4">
                            <h3 className="text-lg font-medium">{listTitle}</h3>
                        </div>

                        <div className="crm-filter-block">
                            <div className="crm-filter-toolbar">
                                <CrmFilterSearch
                                    value={searchTerm}
                                    onChange={setSearchTerm}
                                    onSubmit={submitSearch}
                                    onClear={() => {
                                        resetSearch();
                                    }}
                                    placeholder={searchPlaceholder}
                                    className="w-full"
                                />
                                <div className="crm-month-picker">
                                    <span className="crm-form-label">Month</span>
                                    <select
                                        className="crm-form-select"
                                        value={monthFilter}
                                        onChange={(event) => {
                                            const nextMonth = event.target.value;
                                            setMonthFilter(nextMonth);
                                            submitSearch(undefined, {
                                                month: nextMonth || '',
                                            });
                                        }}
                                    >
                                        <option value="">{monthLabel}</option>
                                        {Array.from({ length: 12 }).map((_, index) => {
                                            const month = String(index + 1).padStart(2, '0');
                                            const year = new Date().getFullYear();
                                            const value = `${year}-${month}`;
                                            return (
                                                <option key={value} value={value}>
                                                    {value}
                                                </option>
                                            );
                                        })}
                                    </select>
                                </div>
                            </div>

                            <CrmFilterTabs
                                items={[
                                    {
                                        label: 'Salesperson',
                                        value: 'salesperson',
                                        active: tab === 'salesperson',
                                        variant: 'stage',
                                        onClick: () => {
                                            applyStatus('salesperson', {
                                                month: monthFilter,
                                            });
                                        },
                                    },
                                    {
                                        label: 'Leader',
                                        value: 'leader',
                                        active: tab === 'leader',
                                        variant: 'stage',
                                        onClick: () => {
                                            applyStatus('leader', { month: monthFilter });
                                        },
                                    },
                                ]}
                            />
                        </div>

                        <div className="crm-table-wrap">
                            {isLeaderTab ? (
                                <table className="crm-table" data-sortable-table="true">
                                    <thead>
                                        <tr>
                                            <th className="w-[50px]"><span className="crm-sort-btn pointer-events-none">No.</span></th>
                                            <th data-sort-index="1">
                                                <span className="crm-sort-btn">Leader <span data-sort-indicator></span></span>
                                            </th>
                                            <th data-sort-index="2" data-sort-type="number">
                                                <span className="crm-sort-btn">Team Size <span data-sort-indicator></span></span>
                                            </th>
                                            <th data-sort-index="3" data-sort-type="number">
                                                <span className="crm-sort-btn">Team Converted Lead <span data-sort-indicator></span></span>
                                            </th>
                                            <th data-sort-index="4" data-sort-type="number">
                                                <span className="crm-sort-btn">Team Completed Deal <span data-sort-indicator></span></span>
                                            </th>
                                            <th data-sort-index="5" data-sort-type="number">
                                                <span className="crm-sort-btn">Team Deal Close Rate <span data-sort-indicator></span></span>
                                            </th>
                                            <th data-sort-index="6" data-sort-type="number">
                                                <span className="crm-sort-btn">Team Avg Complete Day <span data-sort-indicator></span></span>
                                            </th>
                                            <th data-sort-index="7" data-sort-type="number">
                                                <span className="crm-sort-btn">Team Total Commission <span data-sort-indicator></span></span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.length > 0 ? (
                                            rows.map((row, index) => (
                                                <tr key={`${row.name ?? row.leader}-${index}`}>
                                                    <td>{index + 1}</td>
                                                    <td data-sort-value={(row.leader ?? '').toString().toLowerCase()}>
                                                        {row.leader ?? '-'}
                                                    </td>
                                                    <td data-sort-value={row.team_size ?? 0}>
                                                        {formatNumber(row.team_size ?? 0)}
                                                    </td>
                                                    <td data-sort-value={row.converted_leads ?? 0}>
                                                        {formatNumber(row.converted_leads ?? 0)}
                                                    </td>
                                                    <td data-sort-value={row.completed_deals ?? 0}>
                                                        {formatNumber(row.completed_deals ?? 0)}
                                                    </td>
                                                    <td data-sort-value={row.close_rate ?? 0}>
                                                        {formatNumber(row.close_rate ?? 0, 2)}%
                                                    </td>
                                                    <td data-sort-value={row.avg_complete_days ?? ''}>
                                                        {row.avg_complete_days === null
                                                            ? '-'
                                                            : formatNumber(row.avg_complete_days ?? 0, 1)}
                                                    </td>
                                                    <td data-sort-value={row.total_commission ?? 0}>
                                                        RM {formatNumber(row.total_commission ?? 0, 2)}
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan={8} className="crm-table-empty">
                                                    {emptyMessage}
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            ) : (
                                <table className="crm-table" data-sortable-table="true">
                                    <thead>
                                        <tr>
                                            <th className="w-[50px]"><span className="crm-sort-btn pointer-events-none">No.</span></th>
                                            <th data-sort-index="1">
                                                <span className="crm-sort-btn">Salesperson <span data-sort-indicator></span></span>
                                            </th>
                                            <th data-sort-index="2">
                                                <span className="crm-sort-btn">Leader <span data-sort-indicator></span></span>
                                            </th>
                                            <th data-sort-index="3" data-sort-type="number">
                                                <span className="crm-sort-btn">Converted Lead <span data-sort-indicator></span></span>
                                            </th>
                                            <th data-sort-index="4" data-sort-type="number">
                                                <span className="crm-sort-btn">Completed Deal <span data-sort-indicator></span></span>
                                            </th>
                                            <th data-sort-index="5" data-sort-type="number">
                                                <span className="crm-sort-btn">Deal Close Rate <span data-sort-indicator></span></span>
                                            </th>
                                            <th data-sort-index="6" data-sort-type="number">
                                                <span className="crm-sort-btn">Avg Complete Day <span data-sort-indicator></span></span>
                                            </th>
                                            <th data-sort-index="7" data-sort-type="number">
                                                <span className="crm-sort-btn">Total Commission <span data-sort-indicator></span></span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {rows.length > 0 ? (
                                            rows.map((row, index) => (
                                                <tr key={`${row.name ?? row.leader}-${index}`}>
                                                    <td>{index + 1}</td>
                                                    <td data-sort-value={(row.name ?? '').toString().toLowerCase()}>
                                                        {row.name ?? '-'}
                                                    </td>
                                                    <td data-sort-value={(row.leader ?? '').toString().toLowerCase()}>
                                                        {row.leader ?? '-'}
                                                    </td>
                                                    <td data-sort-value={row.converted_leads ?? 0}>
                                                        {formatNumber(row.converted_leads ?? 0)}
                                                    </td>
                                                    <td data-sort-value={row.completed_deals ?? 0}>
                                                        {formatNumber(row.completed_deals ?? 0)}
                                                    </td>
                                                    <td data-sort-value={row.close_rate ?? 0}>
                                                        {formatNumber(row.close_rate ?? 0, 2)}%
                                                    </td>
                                                    <td data-sort-value={row.avg_complete_days ?? ''}>
                                                        {row.avg_complete_days === null
                                                            ? '-'
                                                            : formatNumber(row.avg_complete_days ?? 0, 1)}
                                                    </td>
                                                    <td data-sort-value={row.total_commission ?? 0}>
                                                        RM {formatNumber(row.total_commission ?? 0, 2)}
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan={8} className="crm-table-empty">
                                                    {emptyMessage}
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            )}
                        </div>

                        <div className="mt-4">
                            <CrmPagination links={props.rows?.links ?? []} />
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Salespeople.layout = (page: ReactNode & { props?: SalespeopleProps }) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        {page.props?.pageTitle ?? 'Salesperson Performance'}
                    </h2>
                </div>
            </div>
        }
        headerSubtitle={page.props?.pageSubtitle ?? ''}
    >
        {page}
    </CrmLayout>
);
