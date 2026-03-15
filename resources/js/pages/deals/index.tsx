
import { Head, router, useForm } from '@inertiajs/react';
import { useMemo, useState, type ReactNode } from 'react';
import { CrmFilterSearch } from '@/components/crm/filter-search';
import { CrmFilterTabs } from '@/components/crm/filter-tabs';
import { LoanDetailModal } from '@/components/crm/loan-detail-modal';
import { CrmPagination, type PaginationLink } from '@/components/crm/pagination';
import { useCrmFilters } from '@/hooks/use-crm-filters';
import CrmLayout from '@/layouts/crm-layout';

type DealRow = {
    id: number;
    deal_code?: string | null;
    lead_id?: number | null;
    lead_name?: string | null;
    salesperson_id?: number | null;
    project_name?: string | null;
    developer?: string | null;
    unit_number?: string | null;
    selling_price?: number | null;
    commission_percentage?: number | null;
    commission_amount?: number | null;
    booking_fee?: number | null;
    spa_date?: string | null;
    pipeline?: {
        value?: string | null;
        badge?: string | null;
        locked?: boolean;
    };
    salesperson_name?: string | null;
    leader_name?: string | null;
    created_at?: string | null;
};

type PaginatedDeals = {
    data: DealRow[];
    links: PaginationLink[];
};

type LeadOption = {
    id: number;
    name: string;
    email?: string | null;
};

type SalespersonOption = {
    id: number;
    name: string;
};

type PipelineOption = {
    value: string;
};

type DealProps = {
    deals: PaginatedDeals;
    stages: string[];
    pipelines: PipelineOption[];
    summary: Record<string, number>;
    leads: LeadOption[];
    salespersons: SalespersonOption[];
    canEditSalesperson: boolean;
    currentUserId: number;
};

type DealFormData = {
    id: number | null;
    pipeline: string;
    lead_id: string;
    project_name: string;
    developer: string;
    unit_number: string;
    selling_price: string;
    commission_percentage: string;
    commission_amount: string;
    booking_fee: string;
    spa_date: string;
    salesperson_id: string;
    pipeline_locked: boolean;
};

const shouldShowBooking = (pipeline: string) =>
    pipeline !== 'Lead' && pipeline !== 'Viewing';
const shouldShowSpa = (pipeline: string) =>
    pipeline !== 'Lead' && pipeline !== 'Viewing' && pipeline !== 'Booking';
const requiresBooking = (pipeline: string) =>
    pipeline === 'Booking' || pipeline === 'SPA Signed';
const requiresSpa = (pipeline: string) => pipeline === 'SPA Signed';

export default function Deals({
    deals,
    stages,
    pipelines,
    summary,
    leads,
    salespersons,
    canEditSalesperson,
    currentUserId,
}: DealProps) {
    const {
        searchTerm,
        setSearchTerm,
        statusFilter: stageFilter,
        submitSearch,
        resetSearch,
        applyStatus,
    } = useCrmFilters({
        baseUrl: '/deals',
        statusKey: 'stage',
    });

    const [dealFormOpen, setDealFormOpen] = useState(false);
    const [dealFormMode, setDealFormMode] = useState<'create' | 'edit'>('create');
    const [detailDealId, setDetailDealId] = useState<number | null>(null);

    const defaultSalespersonId = useMemo(() => {
        const hasCurrent = salespersons.some(
            (user) => Number(user.id) === Number(currentUserId),
        );
        if (hasCurrent) {
            return String(currentUserId);
        }
        return String(salespersons[0]?.id ?? '');
    }, [salespersons, currentUserId]);

    const defaultPipeline = pipelines[0]?.value ?? '';

    const emptyForm = (): DealFormData => ({
        id: null,
        pipeline: defaultPipeline,
        lead_id: '',
        project_name: '',
        developer: '',
        unit_number: '',
        selling_price: '',
        commission_percentage: '',
        commission_amount: '',
        booking_fee: '',
        spa_date: '',
        salesperson_id: defaultSalespersonId,
        pipeline_locked: false,
    });

    const form = useForm<DealFormData>(emptyForm());

    const recalcCommission = (priceValue?: string, pctValue?: string) => {
        const price = Number(priceValue ?? form.data.selling_price) || 0;
        const pct = Number(pctValue ?? form.data.commission_percentage) || 0;
        const nextAmount = ((price * pct) / 100).toFixed(2);
        form.setData('commission_amount', nextAmount);
    };

    const openCreate = () => {
        setDealFormMode('create');
        form.setData(emptyForm());
        setDealFormOpen(true);
        setTimeout(() => recalcCommission(), 0);
    };

    const openEdit = (deal: DealRow) => {
        setDealFormMode('edit');
        form.setData({
            ...emptyForm(),
            id: deal.id,
            lead_id: String(deal.lead_id ?? ''),
            salesperson_id: String(deal.salesperson_id ?? ''),
            project_name: deal.project_name ?? '',
            developer: deal.developer ?? '',
            unit_number: deal.unit_number ?? '',
            selling_price: deal.selling_price ? String(deal.selling_price) : '',
            commission_percentage: deal.commission_percentage
                ? String(deal.commission_percentage)
                : '',
            commission_amount: deal.commission_amount
                ? String(deal.commission_amount)
                : '',
            booking_fee: deal.booking_fee ? String(deal.booking_fee) : '',
            spa_date: deal.spa_date ?? '',
            pipeline: deal.pipeline?.value ?? defaultPipeline,
            pipeline_locked: deal.pipeline?.locked ?? false,
        });
        setDealFormOpen(true);
        setTimeout(() => recalcCommission(), 0);
    };

    const closeForm = () => {
        setDealFormOpen(false);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const isEdit = dealFormMode === 'edit' && form.data.id;
        if (isEdit) {
            form.transform((data) => {
                const next: Partial<DealFormData> = { ...data };
                const isLocked = Boolean(next.pipeline_locked);
                delete next.id;
                delete next.pipeline_locked;
                if (isLocked) {
                    delete next.pipeline;
                }
                return next;
            });
            form.put(`/deals/${form.data.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    closeForm();
                    form.transform((data) => data);
                },
            });
            return;
        }
        form.post('/deals', {
            preserveScroll: true,
            onSuccess: () => {
                closeForm();
            },
        });
    };

    const handleDelete = (deal: DealRow) => {
        if (!window.confirm(`Confirm to delete deal ${deal.project_name}?`)) {
            return;
        }
        router.delete(`/deals/${deal.id}`, {
            preserveScroll: true,
        });
    };

    const rows = deals?.data ?? [];
    const showBooking = shouldShowBooking(form.data.pipeline);
    const showSpa = shouldShowSpa(form.data.pipeline);

    return (
        <>
            <Head title="Deals" />

            <div className="space-y-6">
                <section className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <article className="crm-kpi">
                        <p className="crm-kpi-label">Total Deals</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">
                            {(summary?.total ?? 0).toLocaleString('en-US')}
                        </p>
                    </article>
                    {stages.map((stage) => {
                        const key = stage.toLowerCase().replaceAll(' ', '_');
                        const value = Number(summary?.[key] ?? 0);
                        const isCompleted = stage.toLowerCase() === 'completed';
                        return (
                            <article key={stage} className="crm-kpi">
                                <p className="crm-kpi-label">{stage}</p>
                                <p
                                    className={`mt-2 text-2xl font-semibold ${
                                        isCompleted ? 'text-green-600' : 'text-slate-900'
                                    }`}
                                >
                                    {value.toLocaleString('en-US')}
                                </p>
                            </article>
                        );
                    })}
                </section>

                <div className="crm-card">
                    <div className="crm-card-body text-gray-900">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-medium">List of deals</h3>
                        </div>

                        <div className="crm-filter-block">
                            <div className="crm-filter-toolbar">
                                <CrmFilterSearch
                                    value={searchTerm}
                                    onChange={setSearchTerm}
                                    onSubmit={submitSearch}
                                    onClear={resetSearch}
                                    placeholder="Search project or lead..."
                                />
                                <button
                                    type="button"
                                    onClick={openCreate}
                                    className="crm-create-btn"
                                >
                                    Create Deal
                                </button>
                            </div>
                            <CrmFilterTabs
                                items={[
                                    {
                                        label: 'All',
                                        value: '',
                                        active: stageFilter === '',
                                        variant: 'all',
                                        onClick: () => {
                                            applyStatus('');
                                        },
                                    },
                                    ...stages.map((stage) => ({
                                        label: stage,
                                        value: stage,
                                        active: stageFilter === stage,
                                        variant: 'stage' as const,
                                        onClick: () => {
                                            applyStatus(stage);
                                        },
                                    })),
                                ]}
                            />
                        </div>

                        <div className="crm-table-wrap">
                            <table className="crm-table crm-table-center" data-sortable-table="true">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th data-sort-index="2" className="col-left">
                                            <span className="crm-sort-btn">
                                                Project <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="1" className="col-left">
                                            <span className="crm-sort-btn">
                                                Lead <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="3" data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Selling Price <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="4" data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Commission <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="5">
                                            <span className="crm-sort-btn">
                                                Stage <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="6">
                                            <span className="crm-sort-btn">
                                                Salesperson <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="7">
                                            <span className="crm-sort-btn">
                                                Leader <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="8" data-sort-type="date">
                                            <span className="crm-sort-btn">
                                                Created <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody className="whitespace-nowrap">
                                    {rows.length ? (
                                        rows.map((deal) => (
                                            <tr key={deal.id}>
                                                <td>
                                                    <button
                                                        type="button"
                                                        className="crm-action-btn"
                                                        onClick={() => openEdit(deal)}
                                                    >
                                                        <i className="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                </td>
                                                <td
                                                    data-sort-value={deal.deal_code?.toLowerCase()}
                                                    className="col-left"
                                                >
                                                    <button
                                                        type="button"
                                                        className="truncate text-indigo-600 hover:underline"
                                                        onClick={() => setDetailDealId(deal.id)}
                                                    >
                                                        {deal.deal_code ?? '-'}:
                                                    </button>
                                                    <br />
                                                    {deal.project_name ?? '-'}
                                                </td>
                                                <td
                                                    data-sort-value={deal.lead_name?.toLowerCase()}
                                                    className="col-left"
                                                >
                                                    {deal.lead_name ?? '-'}
                                                </td>
                                                <td data-sort-value={deal.selling_price ?? 0}>
                                                    {(deal.selling_price ?? 0).toLocaleString('en-US', {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    })}
                                                </td>
                                                <td data-sort-value={deal.commission_amount ?? 0}>
                                                    {(deal.commission_amount ?? 0).toLocaleString('en-US', {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    })}
                                                </td>
                                                <td
                                                    data-sort-value={
                                                        deal.pipeline?.value?.toLowerCase() ?? ''
                                                    }
                                                >
                                                    <span className={deal.pipeline?.badge ?? ''}>
                                                        {deal.pipeline?.value ?? '-'}
                                                    </span>
                                                </td>
                                                <td
                                                    data-sort-value={
                                                        deal.salesperson_name?.toLowerCase() ?? ''
                                                    }
                                                >
                                                    {deal.salesperson_name ?? '-'}
                                                </td>
                                                <td
                                                    data-sort-value={
                                                        deal.leader_name?.toLowerCase() ?? ''
                                                    }
                                                >
                                                    {deal.leader_name ?? '-'}
                                                </td>
                                                <td
                                                    data-sort-value={deal.created_at ?? ''}
                                                    className="crm-table-date"
                                                >
                                                    {deal.created_at ?? '-'}
                                                </td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        className="crm-action-btn-danger"
                                                        onClick={() => handleDelete(deal)}
                                                    >
                                                        <i className="fa-solid fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={10} className="crm-table-empty">
                                                No deals found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4">
                            <CrmPagination links={deals?.links ?? []} />
                        </div>
                    </div>
                </div>
            </div>
            {dealFormOpen && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                    onClick={(event) => {
                        if (event.currentTarget === event.target) {
                            closeForm();
                        }
                    }}
                >
                    <div className="crm-modal-panel">
                        <div className="crm-modal-header">
                            <h4 className="crm-modal-title">
                                {dealFormMode === 'edit' ? 'Edit Deal' : 'Create Deal'}
                            </h4>
                            <button
                                type="button"
                                className="text-gray-500 hover:text-gray-700"
                                onClick={closeForm}
                            >
                                X
                            </button>
                        </div>
                        <form onSubmit={submit} data-preserve-list-state>
                            <div className="crm-modal-grid">
                                <div>
                                    <label className="crm-form-label" htmlFor="pipeline">
                                        Pipeline Stage
                                    </label>
                                    <select
                                        id="pipeline"
                                        name="pipeline"
                                        value={form.data.pipeline}
                                        onChange={(event) => {
                                            form.setData('pipeline', event.target.value);
                                        }}
                                        className="crm-form-select"
                                        disabled={
                                            dealFormMode === 'edit' && form.data.pipeline_locked
                                        }
                                        required={
                                            !(dealFormMode === 'edit' && form.data.pipeline_locked)
                                        }
                                    >
                                        {pipelines.map((pipeline) => (
                                            <option key={pipeline.value} value={pipeline.value}>
                                                {pipeline.value}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="crm-form-label" htmlFor="deal_salesperson_id">
                                        Assign To
                                    </label>
                                    <select
                                        id="deal_salesperson_id"
                                        name="salesperson_id"
                                        value={form.data.salesperson_id}
                                        onChange={(event) =>
                                            form.setData('salesperson_id', event.target.value)
                                        }
                                        className="crm-form-select"
                                        disabled={!canEditSalesperson}
                                    >
                                        <option value="">Select a salesperson</option>
                                        {salespersons.map((salesperson) => (
                                            <option
                                                key={salesperson.id}
                                                value={salesperson.id}
                                            >
                                                {salesperson.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="crm-form-label" htmlFor="lead_id">
                                        Linked Lead
                                    </label>
                                    <select
                                        id="lead_id"
                                        name="lead_id"
                                        value={form.data.lead_id}
                                        onChange={(event) =>
                                            form.setData('lead_id', event.target.value)
                                        }
                                        className="crm-form-select"
                                        required
                                    >
                                        <option value="">--Select lead--</option>
                                        {leads.map((lead) => (
                                            <option key={lead.id} value={lead.id}>
                                                {lead.name}
                                                {lead.email ? ` - ${lead.email}` : ''}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="crm-form-label" htmlFor="project_name">
                                        Project Name
                                    </label>
                                    <input
                                        id="project_name"
                                        className="crm-form-text"
                                        type="text"
                                        name="project_name"
                                        value={form.data.project_name}
                                        onChange={(event) =>
                                            form.setData('project_name', event.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label" htmlFor="developer">
                                        Developer
                                    </label>
                                    <input
                                        id="developer"
                                        className="crm-form-text"
                                        type="text"
                                        name="developer"
                                        value={form.data.developer}
                                        onChange={(event) =>
                                            form.setData('developer', event.target.value)
                                        }
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label" htmlFor="unit_number">
                                        Unit Number
                                    </label>
                                    <input
                                        id="unit_number"
                                        className="crm-form-text"
                                        type="text"
                                        name="unit_number"
                                        value={form.data.unit_number}
                                        onChange={(event) =>
                                            form.setData('unit_number', event.target.value)
                                        }
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label" htmlFor="selling_price">
                                        Selling Price
                                    </label>
                                    <input
                                        id="selling_price"
                                        className="crm-form-text"
                                        type="number"
                                        step="0.01"
                                        name="selling_price"
                                        value={form.data.selling_price}
                                        onChange={(event) => {
                                            form.setData('selling_price', event.target.value);
                                            recalcCommission(event.target.value, undefined);
                                        }}
                                        required
                                    />
                                </div>
                                <div>
                                    <label
                                        className="crm-form-label"
                                        htmlFor="commission_percentage"
                                    >
                                        Commission %
                                    </label>
                                    <input
                                        id="commission_percentage"
                                        className="crm-form-text"
                                        type="number"
                                        step="0.01"
                                        name="commission_percentage"
                                        value={form.data.commission_percentage}
                                        onChange={(event) => {
                                            form.setData(
                                                'commission_percentage',
                                                event.target.value,
                                            );
                                            recalcCommission(undefined, event.target.value);
                                        }}
                                        required
                                    />
                                </div>
                                <div>
                                    <label
                                        className="crm-form-label"
                                        htmlFor="commission_amount"
                                    >
                                        Commission Amount
                                    </label>
                                    <input
                                        id="commission_amount"
                                        className="crm-form-text crm-form-text-readonly"
                                        type="number"
                                        step="0.01"
                                        name="commission_amount"
                                        value={form.data.commission_amount}
                                        readOnly
                                    />
                                </div>
                                {showBooking && (
                                    <div>
                                        <label
                                            className="crm-form-label"
                                            htmlFor="booking_fee"
                                        >
                                            Booking Fee
                                        </label>
                                        <input
                                            id="booking_fee"
                                            className="crm-form-text"
                                            type="number"
                                            step="0.01"
                                            name="booking_fee"
                                            value={form.data.booking_fee}
                                            onChange={(event) =>
                                                form.setData('booking_fee', event.target.value)
                                            }
                                            required={requiresBooking(form.data.pipeline)}
                                        />
                                    </div>
                                )}
                                {showSpa && (
                                    <div>
                                        <label className="crm-form-label" htmlFor="spa_date">
                                            SPA Date
                                        </label>
                                        <input
                                            id="spa_date"
                                            className="crm-form-text"
                                            type="date"
                                            name="spa_date"
                                            value={form.data.spa_date}
                                            onChange={(event) =>
                                                form.setData('spa_date', event.target.value)
                                            }
                                            required={requiresSpa(form.data.pipeline)}
                                        />
                                    </div>
                                )}
                            </div>
                            <div className="crm-modal-footer">
                                <button
                                    type="button"
                                    className="crm-btn-secondary"
                                    onClick={closeForm}
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="crm-btn-primary"
                                    disabled={form.processing}
                                >
                                    {dealFormMode === 'edit' ? 'Save' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            <LoanDetailModal
                open={detailDealId != null}
                dealId={detailDealId}
                onClose={() => setDetailDealId(null)}
            />
        </>
    );
}

Deals.layout = (page: ReactNode) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Deals
                    </h2>
                </div>
            </div>
        }
    >
        {page}
    </CrmLayout>
);
