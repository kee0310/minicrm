import { Head } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { CrmFilterSearch } from '@/components/crm/filter-search';
import { CrmFilterTabs } from '@/components/crm/filter-tabs';
import { LoanDetailModal } from '@/components/crm/loan-detail-modal';
import { CrmPagination } from '@/components/crm/pagination';
import { useCrmFilters } from '@/hooks/use-crm-filters';
import { useModalForm } from '@/hooks/use-modal-form';
import CrmLayout from '@/layouts/crm-layout';

type CommissionRow = {
    id: number;
    deal_id?: number | null;
    deal_code?: string | null;
    project_name?: string | null;
    salesperson_name?: string | null;
    total: number;
    paid: number;
    remaining: number;
    payment_status: string;
    deal_completed_date?: string | null;
    deal_commission_paid_date?: string | null;
};

type PaginatedCommissions = {
    data: CommissionRow[];
    links: { url: string | null; label: string; active: boolean }[];
};

type CommissionProps = {
    commissions: PaginatedCommissions;
    statusOptions: string[];
    summary: Record<string, number>;
};

type CommissionFormData = {
    paid: string;
    payment_status: string;
};

export default function Commissions({ commissions, statusOptions, summary }: CommissionProps) {
    const [detailDealId, setDetailDealId] = useState<number | null>(null);
    const {
        searchTerm,
        setSearchTerm,
        statusFilter,
        submitSearch,
        resetSearch,
        applyStatus,
    } = useCrmFilters({
        baseUrl: '/commissions',
    });

    const modal = useModalForm<CommissionRow, CommissionFormData>({
        initialData: {
            paid: '',
            payment_status: statusOptions[0] ?? 'Unpaid',
        },
        onOpen: (commission, form) => {
            form.setData({
                paid: String(commission.paid ?? 0),
                payment_status: commission.payment_status ?? 'Unpaid',
            });
        },
        onClose: (form) => {
            form.reset();
        },
    });

    const rows = commissions?.data ?? [];

    return (
        <>
            <Head title="Commission" />

            <div className="space-y-6">
                <section className="grid grid-cols-1 gap-3 sm:grid-cols-5">
                    <article className="crm-kpi">
                        <p className="crm-kpi-label">Commission Eligible</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">
                            {(summary?.eligible ?? 0).toLocaleString('en-US')}
                        </p>
                    </article>
                    <article className="crm-kpi">
                        <p className="crm-kpi-label">Pending Approval</p>
                        <p className="mt-2 text-2xl font-semibold text-amber-700">
                            {(summary?.pending_approval ?? 0).toLocaleString('en-US')}
                        </p>
                    </article>
                    <article className="crm-kpi">
                        <p className="crm-kpi-label">Paid</p>
                        <p className="mt-2 text-2xl font-semibold text-emerald-700">
                            {(summary?.paid ?? 0).toLocaleString('en-US')}
                        </p>
                    </article>
                </section>

                <div className="crm-card">
                    <div className="crm-card-body">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-medium">Lists of Commissions</h3>
                        </div>

                        <div className="crm-filter-block">
                            <CrmFilterSearch
                                value={searchTerm}
                                onChange={setSearchTerm}
                                onSubmit={submitSearch}
                                onClear={resetSearch}
                                placeholder="Search deal, project or salesperson..."
                                className="crm-filter-search-row"
                            />
                            <CrmFilterTabs
                                items={[
                                    {
                                        label: 'All',
                                        value: '',
                                        active: statusFilter === '',
                                        variant: 'all',
                                        onClick: () => {
                                            applyStatus('');
                                        },
                                    },
                                    ...statusOptions.map((status) => ({
                                        label: status,
                                        value: status,
                                        active: statusFilter === status,
                                        variant: 'stage' as const,
                                        onClick: () => {
                                            applyStatus(status);
                                        },
                                    })),
                                ]}
                            />
                        </div>

                        <div className="crm-table-wrap">
                            <table className="crm-table" data-sortable-table="true">
                                <thead>
                                    <tr>
                                        <th className="text-right"></th>
                                        <th data-sort-index="1">
                                            <span className="crm-sort-btn">
                                                Salesperson Project{' '}
                                                <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="2">
                                            <span className="crm-sort-btn">
                                                Salesperson <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="3" data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Total (RM) <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="4" data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Paid (RM) <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="5" data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Remaining (RM) <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="6">
                                            <span className="crm-sort-btn">
                                                Payment Status{' '}
                                                <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="7" data-sort-type="date">
                                            <span className="crm-sort-btn">
                                                Deal Completed Date{' '}
                                                <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index="8" data-sort-type="date">
                                            <span className="crm-sort-btn">
                                                Commission Paid Date{' '}
                                                <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {rows.length ? (
                                        rows.map((commission) => {
                                            const statusClass =
                                                commission.payment_status === 'Paid'
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-amber-100 text-amber-700';
                                            return (
                                                <tr key={commission.id}>
                                                    <td className="text-right">
                                                        <button
                                                            type="button"
                                                            className="crm-action-btn"
                                                            onClick={() => modal.open(commission)}
                                                        >
                                                            <i className="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                    </td>
                                                    <td
                                                        data-sort-value={
                                                            commission.deal_code?.toLowerCase() ?? ''
                                                        }
                                                    >
                                                        <button
                                                            type="button"
                                                            className="truncate text-indigo-600 hover:underline"
                                                            onClick={() =>
                                                                commission.deal_id &&
                                                                setDetailDealId(commission.deal_id)
                                                            }
                                                        >
                                                            {commission.deal_code ?? '-'}
                                                        </button>
                                                        :<br />
                                                        {commission.project_name ?? '-'}
                                                    </td>
                                                    <td
                                                        data-sort-value={
                                                            commission.salesperson_name?.toLowerCase() ?? ''
                                                        }
                                                    >
                                                        {commission.salesperson_name ?? '-'}
                                                    </td>
                                                    <td data-sort-value={commission.total}>
                                                        {commission.total.toLocaleString('en-US', {
                                                            minimumFractionDigits: 2,
                                                            maximumFractionDigits: 2,
                                                        })}
                                                    </td>
                                                    <td data-sort-value={commission.paid}>
                                                        {commission.paid.toLocaleString('en-US', {
                                                            minimumFractionDigits: 2,
                                                            maximumFractionDigits: 2,
                                                        })}
                                                    </td>
                                                    <td data-sort-value={commission.remaining}>
                                                        {commission.remaining.toLocaleString('en-US', {
                                                            minimumFractionDigits: 2,
                                                            maximumFractionDigits: 2,
                                                        })}
                                                    </td>
                                                    <td
                                                        data-sort-value={
                                                            commission.payment_status?.toLowerCase() ?? ''
                                                        }
                                                    >
                                                        <span
                                                            className={`inline-flex text-xs items-center px-2.5 py-1 rounded-full font-semibold ${statusClass}`}
                                                        >
                                                            {commission.payment_status}
                                                        </span>
                                                    </td>
                                                    <td
                                                        data-sort-value={
                                                            commission.deal_completed_date ?? ''
                                                        }
                                                        className="crm-table-date"
                                                    >
                                                        {commission.deal_completed_date ?? '-'}
                                                    </td>
                                                    <td
                                                        data-sort-value={
                                                            commission.deal_commission_paid_date ?? ''
                                                        }
                                                        className="crm-table-date"
                                                    >
                                                        {commission.deal_commission_paid_date ?? '-'}
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td colSpan={9} className="crm-table-empty">
                                                No commission records found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4">
                            <CrmPagination links={commissions?.links ?? []} />
                        </div>
                    </div>
                </div>
            </div>

            {modal.isOpen && modal.selected && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                    onClick={(event) => {
                        if (event.currentTarget === event.target) {
                            modal.close();
                        }
                    }}
                >
                    <div className="crm-modal-panel">
                        <div className="crm-modal-header">
                            <h4 className="crm-modal-title">Edit Commission</h4>
                            <button
                                type="button"
                                className="text-gray-500 hover:text-gray-700"
                                onClick={modal.close}
                            >
                                X
                            </button>
                        </div>

                        <form
                            onSubmit={modal.submit((form, commission) => {
                                form.put(`/commissions/${commission.id}`, {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        modal.close();
                                    },
                                });
                            })}
                            data-preserve-list-state
                        >
                            <div className="crm-modal-grid">
                                <div>
                                    <label className="crm-form-label">Project</label>
                                    <input
                                        type="text"
                                        value={`${modal.selected.deal_code ?? '-'} - ${
                                            modal.selected.project_name ?? '-'
                                        }`}
                                        className="crm-form-input crm-form-input-readonly"
                                        readOnly
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Salesperson</label>
                                    <input
                                        type="text"
                                        value={modal.selected.salesperson_name ?? '-'}
                                        className="crm-form-input crm-form-input-readonly"
                                        readOnly
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Total</label>
                                    <input
                                        type="text"
                                        value={Number(modal.selected.total ?? 0).toFixed(2)}
                                        className="crm-form-input crm-form-input-readonly"
                                        readOnly
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Paid</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="paid"
                                        value={modal.form.data.paid}
                                        onChange={(event) =>
                                            modal.form.setData('paid', event.target.value)
                                        }
                                        className="crm-form-input"
                                        required
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <label className="crm-form-label">Payment Status</label>
                                    <select
                                        name="payment_status"
                                        value={modal.form.data.payment_status}
                                        onChange={(event) =>
                                            modal.form.setData(
                                                'payment_status',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-select"
                                        required
                                    >
                                        {statusOptions.map((status) => (
                                            <option key={status} value={status}>
                                                {status}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="crm-modal-footer">
                                <button
                                    type="button"
                                    onClick={modal.close}
                                    className="crm-btn-secondary"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="crm-btn-primary"
                                    disabled={modal.form.processing}
                                >
                                    Save
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

Commissions.layout = (page: ReactNode) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Commission
                    </h2>
                </div>
            </div>
        }
    >
        {page}
    </CrmLayout>
);
