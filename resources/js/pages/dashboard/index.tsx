import { Head, Link } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import { bootDashboard } from '@/dashboard';
import CrmLayout from '@/layouts/crm-layout';

type DashboardProps = {
    dashboardTitle?: string;
    dashboardSubtitle?: string;
    monthNav: {
        label: string;
        current?: string;
        prev_url: string;
        next_url: string;
    };
    executive: Record<string, number>;
    executiveTrends: Record<string, number>;
    dashboardChartsEndpoint?: string | null;
    dashboardPipelineDetailsEndpoint?: string | null;
    forecast: Record<string, number>;
    lead: Record<string, number>;
    deal: Record<string, number>;
    loan: Record<string, number>;
    legal: Record<string, number>;
    finance: Record<string, number>;
    canViewMonthlyPerformance?: boolean;
    canViewExecutive?: boolean;
    canViewSales?: boolean;
    canViewLoan?: boolean;
    canViewLegal?: boolean;
    canViewFinance?: boolean;
};

type WindowWithCrm = Window & {
    __crmOpenPipelineStageModal?: () => void;
    __crmClosePipelineStageModal?: () => void;
    Chart?: unknown;
};

const withAnimDelay = (delay: string): React.CSSProperties => ({
    ['--crm-anim-delay' as string]: delay,
});

function formatNumber(value: number, decimals = 0) {
    return Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

function TrendBadge({ value }: { value: number }) {
    const trendValue = Number(value || 0);
    const trendClass =
        trendValue > 0
            ? 'crm-kpi-trend-up'
            : trendValue < 0
              ? 'crm-kpi-trend-down'
              : 'crm-kpi-trend-flat';
    const trendPrefix = trendValue > 0 ? '+' : '';
    return (
        <p className={`mt-2 text-xs ${trendClass}`}>
            {trendPrefix}
            {formatNumber(trendValue, 2)}%
        </p>
    );
}

function ChartCard({
    title,
    children,
    className,
    header,
    style,
}: {
    title?: string;
    children: ReactNode;
    className?: string;
    header?: ReactNode;
    style?: React.CSSProperties;
}) {
    return (
        <section className={`crm-card ${className ?? ''}`} style={style}>
            {header ? (
                <header className="border-b border-slate-200 px-6 py-4">
                    {header}
                </header>
            ) : title ? (
                <header className="border-b border-slate-200 px-6 py-4">
                    <h3>{title}</h3>
                </header>
            ) : null}
            <div className="crm-card-body">{children}</div>
        </section>
    );
}
function PipelineStageModal() {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        const win = window as WindowWithCrm;
        win.__crmOpenPipelineStageModal = () => setOpen(true);
        win.__crmClosePipelineStageModal = () => setOpen(false);
        return () => {
            delete win.__crmOpenPipelineStageModal;
            delete win.__crmClosePipelineStageModal;
        };
    }, []);

    return (
        <div
            className={`fixed inset-0 z-50 overflow-y-auto px-4 py-8 sm:px-6 ${open ? 'block' : 'hidden'}`}
        >
            <div
                className="fixed inset-0 transform transition-all"
                onClick={() => setOpen(false)}
            >
                <div className="absolute inset-0 bg-slate-900/35"></div>
            </div>
            <div className="fixed left-1/2 top-1/2 z-[60] mb-0 w-[calc(100vw-2rem)] -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl sm:w-full sm:max-w-4xl">
                <div className="border-b border-slate-200 px-5 py-3">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h3
                                id="pipeline-stage-modal-title"
                                className="text-base font-semibold text-slate-900"
                            >
                                Pipeline Stage
                            </h3>
                            <p
                                id="pipeline-stage-modal-subtitle"
                                className="mt-0.5 text-xs text-slate-500"
                            ></p>
                        </div>
                        <button
                            type="button"
                            className="text-slate-500 hover:text-slate-700"
                            onClick={() => setOpen(false)}
                        >
                            <span className="sr-only">Close</span>
                            <i className="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>
                </div>
                <div className="mb-2 max-h-[70vh] overflow-y-auto">
                    <div
                        id="pipeline-stage-modal-loading"
                        className="hidden py-8 text-center text-sm text-slate-500"
                    >
                        Loading stage details...
                    </div>
                    <div
                        id="pipeline-stage-modal-empty"
                        className="hidden py-8 text-center text-sm text-slate-500"
                    >
                        No records found for this stage.
                    </div>
                    <div
                        id="pipeline-stage-modal-error"
                        className="hidden py-8 text-center text-sm text-rose-600"
                    >
                        Failed to load stage details.
                    </div>
                    <div
                        id="pipeline-stage-modal-table-wrap"
                        className="hidden overflow-x-auto px-5 py-4"
                    >
                        <table className="w-full divide-y divide-slate-200 border border-slate-200 text-sm">
                            <thead className="bg-slate-100 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                <tr>
                                    <th className="px-3 py-2 text-center">
                                        No.
                                    </th>
                                    <th className="px-3 py-2">Deal ID</th>
                                    <th className="px-3 py-2">Project Name</th>
                                    <th className="px-3 py-2">Salesperson</th>
                                    <th className="px-3 py-2">Leader</th>
                                    <th className="px-3 py-2">Created Date</th>
                                    <th
                                        id="pipeline-stage-modal-stage-date-header"
                                        className="px-3 py-2"
                                    >
                                        Stage Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                id="pipeline-stage-modal-tbody"
                                className="divide-y divide-slate-200 bg-white text-slate-700"
                            ></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function Dashboard(props: DashboardProps) {
    const selectedMonth = props.monthNav?.current ?? '';
    const salespeopleHref = (tab: 'salesperson' | 'leader') =>
        selectedMonth
            ? `/dashboard/salespeople?tab=${tab}&month=${selectedMonth}`
            : `/dashboard/salespeople?tab=${tab}`;

    useEffect(() => {
        let mounted = true;
        const init = async () => {
            const module = await import('chart.js/auto');
            if (mounted) {
                (window as WindowWithCrm).Chart = module.default;
                await bootDashboard();
            }
        };
        void init();
        return () => {
            mounted = false;
        };
    }, []);

    const dashboardData = {
        chartEndpoint: props.dashboardChartsEndpoint ?? null,
        pipelineDetailsEndpoint: props.dashboardPipelineDetailsEndpoint ?? null,
        monthLabel: props.monthNav?.label ?? '',
        totalLeadsMonth: Number(props.executive?.new_leads_month ?? 0),
        salesPerformance: null,
        commissionTrend: null,
        leadsBySource: [],
        pipelineStages: [],
    };

    const kpis = [
        {
            label: 'New Leads',
            value: formatNumber(props.executive?.new_leads_month ?? 0),
            trend: props.executiveTrends?.new_leads_month ?? 0,
        },
        {
            label: 'New Deals',
            value: formatNumber(props.executive?.new_deals_month ?? 0),
            trend: props.executiveTrends?.new_deals_month ?? 0,
        },
        {
            label: 'New Bookings',
            value: formatNumber(props.executive?.new_bookings ?? 0),
            trend: props.executiveTrends?.new_bookings ?? 0,
        },
        {
            label: 'Completed Deals',
            value: formatNumber(props.executive?.completed_deals_month ?? 0),
            trend: props.executiveTrends?.completed_deals_month ?? 0,
        },
        {
            label: 'Submited Loan',
            value: formatNumber(props.executive?.new_loan_cases_month ?? 0),
            trend: props.executiveTrends?.new_loan_cases_month ?? 0,
        },
        {
            label: 'Approved Loan',
            value: formatNumber(props.executive?.approved_loans_month ?? 0),
            trend: props.executiveTrends?.approved_loans_month ?? 0,
        },
        {
            label: 'Loan Approval Rate',
            value: `${formatNumber(props.executive?.loan_approval_rate ?? 0, 2)}%`,
            trend: props.executiveTrends?.loan_approval_rate ?? 0,
        },
        {
            label: 'Commission Earned',
            value: `RM ${formatNumber(props.executive?.total_commission_month ?? 0, 2)}`,
            trend: props.executiveTrends?.total_commission_month ?? 0,
        },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <div
                id="dashboard-data"
                className="hidden"
                aria-hidden="true"
                data-dashboard={JSON.stringify(dashboardData)}
            ></div>
            <div className="relative">
                <div
                    id="dashboard-loading"
                    className="absolute inset-0 z-20 -mx-6 -my-4 flex justify-center bg-white/70 backdrop-blur-sm"
                    style={{ pointerEvents: 'none' }}
                >
                    <div className="mt-[40vh] flex h-min items-center gap-2 rounded-full border border-slate-200 bg-white/90 px-4 py-2 text-sm text-slate-500 shadow-sm">
                        <span className="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-slate-600"></span>
                        <span>Loading dashboard...</span>
                    </div>
                </div>

                <div className="crm-dashboard-scroll relative min-h-[calc(100vh-6rem)] space-y-4 overflow-y-auto bg-slate-50 p-4">
                    <div className="relative z-[25] flex justify-end">
                        <div className="crm-dash-month-nav inline-flex items-center rounded-lg border border-slate-300 bg-white p-1 shadow-sm">
                            <Link
                                href={props.monthNav.prev_url}
                                className="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-600 hover:bg-slate-100"
                            >
                                <i className="fa-solid fa-chevron-left text-xs"></i>
                            </Link>
                            <span className="inline-flex min-w-28 items-center justify-center rounded-md px-3 py-1.5 text-sm font-semibold text-slate-800">
                                {props.monthNav.label}
                            </span>
                            <Link
                                href={props.monthNav.next_url}
                                className="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-600 hover:bg-slate-100"
                            >
                                <i className="fa-solid fa-chevron-right text-xs"></i>
                            </Link>
                        </div>
                    </div>

                    <section className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        {kpis.map((item) => (
                            <article
                                key={item.label}
                                className="crm-kpi crm-anim-fade-up"
                            >
                                <p className="crm-kpi-label">{item.label}</p>
                                <p className="crm-countup mt-2 text-[1.5rem] font-semibold leading-none text-slate-900">
                                    {item.value}
                                </p>
                                <TrendBadge value={Number(item.trend)} />
                            </article>
                        ))}
                    </section>

                    <section className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <ChartCard
                            title="Leads by Source"
                            className="crm-anim-pop"
                            style={withAnimDelay('120ms')}
                        >
                            <div id="dashboard-leads-source"></div>
                        </ChartCard>

                        <ChartCard
                            title="Pipeline Overview"
                            className="crm-anim-pop"
                            style={withAnimDelay('180ms')}
                        >
                            <div id="dashboard-pipeline-overview"></div>
                        </ChartCard>

                        <ChartCard
                            title="Total Commission (Past 5 Months)"
                            className="crm-anim-pop"
                            style={withAnimDelay('240ms')}
                        >
                            <div className="flex h-[300px] items-center pt-4">
                                <canvas id="dashboard-total-commission-line"></canvas>
                            </div>
                        </ChartCard>
                    </section>
                    {props.canViewMonthlyPerformance ? (
                        <section
                            className="crm-anim-fade-up"
                            style={withAnimDelay('260ms')}
                        >
                            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <ChartCard
                                    className="crm-dash-chart-panel"
                                    header={
                                        <div className="relative flex h-8 items-center">
                                            <Link
                                                href={salespeopleHref(
                                                    'salesperson',
                                                )}
                                                className="mr-3 rounded-lg border border-slate-300 bg-blue-500 px-3 py-2 text-[8px] font-semibold uppercase text-white shadow-sm transition hover:bg-white hover:text-slate-700"
                                            >
                                                View All
                                            </Link>
                                            <h3 className="absolute left-1/2 -translate-x-1/2">
                                                Salesperson
                                            </h3>
                                        </div>
                                    }
                                >
                                    <div className="m-auto h-[360px] max-w-[500px]">
                                        <canvas id="salespersonPerformanceChart"></canvas>
                                    </div>
                                </ChartCard>
                                <ChartCard
                                    className="crm-dash-chart-panel"
                                    header={
                                        <div className="relative flex h-8 items-center">
                                            <Link
                                                href={salespeopleHref('leader')}
                                                className="mr-3 rounded-lg border border-slate-300 bg-blue-500 px-3 py-2 text-[8px] font-semibold uppercase text-white shadow-sm transition hover:bg-white hover:text-slate-700"
                                            >
                                                View All
                                            </Link>
                                            <h3 className="absolute left-1/2 -translate-x-1/2">
                                                Leader
                                            </h3>
                                        </div>
                                    }
                                >
                                    <div className="m-auto h-[360px] max-w-[500px]">
                                        <canvas id="leaderPerformanceChart"></canvas>
                                    </div>
                                </ChartCard>
                            </div>
                        </section>
                    ) : null}

                    <section>
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <ChartCard
                                title="Lead Status (All)"
                                className="crm-anim-fade-up"
                                style={withAnimDelay('125ms')}
                            >
                                <div className="crm-anim-stagger grid grid-cols-2 gap-4">
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Total Leads
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.lead?.total_leads ?? 0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Losted Leads
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold text-rose-700">
                                            {formatNumber(
                                                props.lead?.total_lost_leads ??
                                                    0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Contacted Leads
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.lead?.contacted_leads ??
                                                    0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Scheduled Leads
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.lead?.scheduled_leads ??
                                                    0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Leads Converted
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.lead?.leads_converted ??
                                                    0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Leads Converted Rate
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.lead
                                                    ?.leads_converted_rate ?? 0,
                                                2,
                                            )}
                                            %
                                        </p>
                                    </div>
                                </div>
                            </ChartCard>

                            <ChartCard
                                title="Deal Status (All)"
                                className="crm-anim-fade-up"
                                style={withAnimDelay('130ms')}
                            >
                                <div className="crm-anim-stagger grid grid-cols-2 gap-4 md:grid-cols-2">
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Total Deals
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.deal?.total_deals ?? 0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Deal Close Rate
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.deal?.close_rate ?? 0,
                                                2,
                                            )}
                                            %
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Completed Deals
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold text-emerald-700">
                                            {formatNumber(
                                                props.deal?.completed_deals ??
                                                    0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Active Deals
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.deal?.incomplete_deals ??
                                                    0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Avg Complete Day
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {props.deal?.avg_completion_days ===
                                            null
                                                ? '-'
                                                : formatNumber(
                                                      props.deal
                                                          ?.avg_completion_days ??
                                                          0,
                                                      1,
                                                  )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Avg Commission / Deal
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            RM{' '}
                                            {formatNumber(
                                                props.deal
                                                    ?.avg_commission_per_deal ??
                                                    0,
                                                2,
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </ChartCard>

                            <ChartCard
                                title="Loan Status (All)"
                                className="crm-anim-fade-up md:col-span-2"
                                style={withAnimDelay('140ms')}
                            >
                                <div className="crm-anim-stagger grid grid-cols-2 gap-4 md:grid-cols-4">
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Total Loan Cases
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.loan?.total_cases ?? 0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Pending Document Cases
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.loan
                                                    ?.pending_document_cases ??
                                                    0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Submitted to Bank
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.loan?.submitted_to_bank ??
                                                    0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Approved
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold text-emerald-700">
                                            {formatNumber(
                                                props.loan?.approved ?? 0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Rejected
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold text-rose-700">
                                            {formatNumber(
                                                props.loan?.rejected ?? 0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Approval Rate
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.loan?.approval_rate ?? 0,
                                                2,
                                            )}
                                            %
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Average Approval Days
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {props.loan
                                                ?.average_approval_days === null
                                                ? '-'
                                                : props.loan
                                                      ?.average_approval_days}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            High DSR Cases
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold text-amber-700">
                                            {formatNumber(
                                                props.loan?.high_dsr_cases ?? 0,
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </ChartCard>

                            <ChartCard
                                title="Legal Status (All)"
                                className="crm-anim-fade-up"
                                style={withAnimDelay('180ms')}
                            >
                                <div className="crm-anim-stagger grid grid-cols-2 gap-4 md:grid-cols-2">
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Total Legal Cases
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.legal?.total_cases ?? 0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Completed Cases
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold text-emerald-700">
                                            {formatNumber(
                                                props.legal?.completed_cases ??
                                                    0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            SPA Drafting
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.legal?.drafting ?? 0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Awaiting Client Signature
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.legal
                                                    ?.awaiting_client_signature ??
                                                    0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Awaiting Bank
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.legal?.awaiting_bank ?? 0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Overdue Cases (&gt;14 days)
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold text-rose-700">
                                            {formatNumber(
                                                props.legal?.overdue_cases ?? 0,
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </ChartCard>

                            <ChartCard
                                title="Commission Status (All)"
                                className="crm-anim-fade-up"
                                style={withAnimDelay('220ms')}
                            >
                                <div className="crm-anim-stagger mb-6 grid grid-cols-2 gap-4 md:grid-cols-2">
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Total Commission
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            {formatNumber(
                                                props.finance?.eligible ?? 0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Total Ammount (RM)
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold">
                                            RM{' '}
                                            {formatNumber(
                                                props.finance
                                                    ?.total_commission ?? 0,
                                                2,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">Paid</p>
                                        <p className="mt-2 text-2xl font-semibold text-emerald-700">
                                            {formatNumber(
                                                props.finance?.paid ?? 0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Unpaid
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold text-amber-700">
                                            {formatNumber(
                                                props.finance
                                                    ?.pending_approval ?? 0,
                                            )}
                                        </p>
                                    </div>
                                    <div className="crm-kpi crm-kpi-2">
                                        <p className="crm-kpi-label-2">
                                            Clawback Cases
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold text-rose-700">
                                            {formatNumber(
                                                props.finance?.clawback ?? 0,
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </ChartCard>
                        </div>
                    </section>
                </div>
            </div>

            <PipelineStageModal />
        </>
    );
}

Dashboard.layout = (page: ReactNode & { props?: DashboardProps }) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {page.props?.dashboardTitle ?? 'CRM Dashboard'}
                    </h2>
                </div>
            </div>
        }
        headerSubtitle={
            page.props?.dashboardSubtitle ??
            'Executive and operational overview'
        }
    >
        {page}
    </CrmLayout>
);
