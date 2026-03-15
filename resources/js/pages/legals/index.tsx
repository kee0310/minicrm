
import { Head } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { CrmFilterSearch } from '@/components/crm/filter-search';
import { CrmFilterTabs } from '@/components/crm/filter-tabs';
import { LoanDetailModal } from '@/components/crm/loan-detail-modal';
import { CrmPagination, type PaginationLink } from '@/components/crm/pagination';
import { useCrmFilters } from '@/hooks/use-crm-filters';
import { useModalForm } from '@/hooks/use-modal-form';
import CrmLayout from '@/layouts/crm-layout';

type LegalDealRow = {
    id: number;
    deal_code?: string | null;
    project_name?: string | null;
    client_name?: string | null;
    legal_officer_name?: string | null;
    legal_officer_id?: number | null;
    lawyer_firm?: string | null;
    spa_date?: string | null;
    loan_agreement_date?: string | null;
    completion_date?: string | null;
    stamp_duty?: boolean | null;
    status?: string | null;
    has_record?: boolean;
};

type PaginatedDeals = {
    data: LegalDealRow[];
    links: PaginationLink[];
};

type Officer = {
    id: number;
    name: string;
};

type LegalProps = {
    deals: PaginatedDeals;
    statusOptions: string[];
    canManageLoanRecords: boolean;
    legalOfficers: Officer[];
    currentLegalOfficerId?: number | null;
    summary: Record<string, number>;
    isLegalOfficer: boolean;
};

type LegalFormData = {
    status: string;
    lawyer_firm: string;
    spa_date: string;
    loan_agreement_date: string;
    completion_date: string;
    stamp_duty: boolean;
    assign_to: string;
};

const statusBadgeClass = (status?: string | null) => {
    switch (status) {
        case 'Drafting':
            return 'bg-gray-200 text-gray-700';
        case 'Pending Bank':
            return 'bg-blue-100 text-blue-700';
        case 'Pending Customer Signature':
            return 'bg-amber-100 text-amber-700';
        case 'Completed':
            return 'bg-green-100 text-green-700';
        default:
            return '';
    }
};

export default function Legals({
    deals,
    statusOptions,
    canManageLoanRecords,
    legalOfficers,
    currentLegalOfficerId,
    summary,
    isLegalOfficer,
}: LegalProps) {
    const {
        searchTerm,
        setSearchTerm,
        statusFilter,
        submitSearch,
        resetSearch,
        applyStatus,
    } = useCrmFilters({
        baseUrl: '/legals',
    });

    const [detailDealId, setDetailDealId] = useState<number | null>(null);
    const modal = useModalForm<LegalDealRow, LegalFormData>({
        initialData: {
            status: '',
            lawyer_firm: '',
            spa_date: '',
            loan_agreement_date: '',
            completion_date: '',
            stamp_duty: false,
            assign_to: '',
        },
        onOpen: (deal, form) => {
            form.setData({
                status: deal.status ?? 'Drafting',
                lawyer_firm: deal.lawyer_firm ?? '',
                spa_date: deal.spa_date ?? '',
                loan_agreement_date: deal.loan_agreement_date ?? '',
                completion_date: deal.completion_date ?? '',
                stamp_duty: Boolean(deal.stamp_duty),
                assign_to: String(deal.legal_officer_id ?? currentLegalOfficerId ?? ''),
            });
        },
        onClose: (form) => {
            form.transform((data) => data);
            form.reset();
        },
    });

    const rows = deals?.data ?? [];
    const sortStart = canManageLoanRecords ? 1 : 0;

    return (
        <>
            <Head title="Legal" />

            <div className="space-y-6">
                <section className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <article className="crm-kpi">
                        <p className="crm-kpi-label">New</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">
                            {(summary?.new ?? 0).toLocaleString('en-US')}
                        </p>
                    </article>
                    {statusOptions.map((status) => {
                        const key = status.toLowerCase().replaceAll(' ', '_');
                        const value = Number(summary?.[key] ?? 0);
                        const isCompleted = status.toLowerCase() === 'completed';
                        return (
                            <article key={status} className="crm-kpi">
                                <p className="crm-kpi-label">{status}</p>
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
                    <div className="crm-card-body">
                        <div className="crm-filter-block">
                            <CrmFilterSearch
                                value={searchTerm}
                                onChange={setSearchTerm}
                                onSubmit={submitSearch}
                                onClear={resetSearch}
                                placeholder="Search project, client or lawyer firm..."
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
                                    {
                                        label: 'New',
                                        value: 'New',
                                        active: statusFilter === 'New',
                                        variant: 'stage',
                                        onClick: () => {
                                            applyStatus('New');
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
                            <table className="crm-table crm-table-center" data-sortable-table="true">
                                <thead>
                                    <tr>
                                        {canManageLoanRecords && <th></th>}
                                        <th data-sort-index={sortStart} className="col-left">
                                            <span className="crm-sort-btn">
                                                Project <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 1} className="col-left">
                                            <span className="crm-sort-btn">
                                                Client <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 2}>
                                            <span className="crm-sort-btn">
                                                Legal Officer <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 3}>
                                            <span className="crm-sort-btn">
                                                Lawyer Firm <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th
                                            data-sort-index={sortStart + 4}
                                            data-sort-type="date"
                                            className="whitespace-nowrap"
                                        >
                                            <span className="crm-sort-btn">
                                                SPA Date <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 5} data-sort-type="date">
                                            <span className="crm-sort-btn">
                                                Loan Agreement Date{' '}
                                                <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 6} data-sort-type="date">
                                            <span className="crm-sort-btn">
                                                Completion Date <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 7} className="w-8%">
                                            <span className="crm-sort-btn">
                                                Stamp Duty <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 8}>
                                            <span className="crm-sort-btn">
                                                Status <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.length ? (
                                        rows.map((deal) => {
                                            const hideEdit =
                                                isLegalOfficer && deal.status === 'Completed';
                                            return (
                                                <tr key={deal.id}>
                                                    {canManageLoanRecords && (
                                                        <td>
                                                            {!hideEdit && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => modal.open(deal)}
                                                                    className="crm-action-btn"
                                                                >
                                                                    <i
                                                                        className={`fa-solid ${
                                                                            deal.has_record
                                                                                ? 'fa-pen-to-square'
                                                                                : 'fa-plus'
                                                                        }`}
                                                                    ></i>
                                                                </button>
                                                            )}
                                                        </td>
                                                    )}
                                                    <td
                                                        className="col-left"
                                                        data-sort-value={
                                                            deal.deal_code?.toLowerCase() ?? ''
                                                        }
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
                                                        className="col-left"
                                                        data-sort-value={
                                                            deal.client_name?.toLowerCase() ?? ''
                                                        }
                                                    >
                                                        {deal.client_name ?? '-'}
                                                    </td>
                                                    <td
                                                        data-sort-value={
                                                            deal.legal_officer_name?.toLowerCase() ?? ''
                                                        }
                                                    >
                                                        {deal.legal_officer_name ?? '-'}
                                                    </td>
                                                    <td
                                                        data-sort-value={
                                                            deal.lawyer_firm?.toLowerCase() ?? ''
                                                        }
                                                    >
                                                        {deal.lawyer_firm ?? '-'}
                                                    </td>
                                                    <td
                                                        data-sort-value={deal.spa_date ?? ''}
                                                        className="crm-table-date whitespace-nowrap"
                                                    >
                                                        {deal.spa_date ?? '-'}
                                                    </td>
                                                    <td
                                                        data-sort-value={
                                                            deal.loan_agreement_date ?? ''
                                                        }
                                                        className="crm-table-date whitespace-nowrap"
                                                    >
                                                        {deal.loan_agreement_date ?? '-'}
                                                    </td>
                                                    <td
                                                        data-sort-value={deal.completion_date ?? ''}
                                                        className="crm-table-date whitespace-nowrap"
                                                    >
                                                        {deal.completion_date ?? '-'}
                                                    </td>
                                                    <td
                                                        data-sort-value={
                                                            deal.stamp_duty == null
                                                                ? '-'
                                                                : deal.stamp_duty
                                                                  ? 'yes'
                                                                  : 'no'
                                                        }
                                                    >
                                                        {deal.stamp_duty == null ? (
                                                            '-'
                                                        ) : deal.stamp_duty ? (
                                                            <i
                                                                className="fa-solid fa-check text-green-600"
                                                                aria-label="Stamp duty yes"
                                                            ></i>
                                                        ) : (
                                                            <i
                                                                className="fa-solid fa-xmark text-red-600"
                                                                aria-label="Stamp duty no"
                                                            ></i>
                                                        )}
                                                    </td>
                                                    <td
                                                        data-sort-value={
                                                            deal.status?.toLowerCase() ?? ''
                                                        }
                                                    >
                                                        <span
                                                            className={`inline-flex text-xs items-center px-2.5 py-1 rounded-full font-semibold ${statusBadgeClass(
                                                                deal.status,
                                                            )}`}
                                                        >
                                                            {deal.status}
                                                        </span>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={canManageLoanRecords ? 11 : 10}
                                                className="crm-table-empty"
                                            >
                                                No loan approved deals found.
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

            {modal.isOpen && modal.selected && canManageLoanRecords && (
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
                            <h4 className="crm-modal-title">
                                {modal.selected.has_record
                                    ? 'Edit Legal Case'
                                    : 'Add Legal Case'}
                            </h4>
                            <button
                                type="button"
                                className="text-gray-500 hover:text-gray-700"
                                onClick={modal.close}
                            >
                                X
                            </button>
                        </div>

                        <form
                            onSubmit={modal.submit((form, deal) => {
                                form.transform((data) => ({
                                    ...data,
                                    stamp_duty: data.stamp_duty ? 1 : 0,
                                }));
                                form.put(`/legals/${deal.id}`, {
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
                                    <label className="crm-form-label">Lawyer Firm</label>
                                    <input
                                        type="text"
                                        name="lawyer_firm"
                                        value={modal.form.data.lawyer_firm}
                                        onChange={(event) =>
                                            modal.form.setData(
                                                'lawyer_firm',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">SPA Date</label>
                                    <input
                                        type="date"
                                        name="spa_date"
                                        value={modal.form.data.spa_date}
                                        onChange={(event) =>
                                            modal.form.setData(
                                                'spa_date',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Loan Agreement Date</label>
                                    <input
                                        type="date"
                                        name="loan_agreement_date"
                                        value={modal.form.data.loan_agreement_date}
                                        onChange={(event) =>
                                            modal.form.setData(
                                                'loan_agreement_date',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Completion Date</label>
                                    <input
                                        type="date"
                                        name="completion_date"
                                        value={modal.form.data.completion_date}
                                        onChange={(event) =>
                                            modal.form.setData(
                                                'completion_date',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Status</label>
                                    <select
                                        name="status"
                                        value={modal.form.data.status}
                                        onChange={(event) =>
                                            modal.form.setData('status', event.target.value)
                                        }
                                        className="crm-form-select"
                                    >
                                        <option value="">-</option>
                                        {statusOptions.map((status) => (
                                            <option key={status} value={status}>
                                                {status}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="crm-form-label">Assign To</label>
                                    <select
                                        name="assign_to"
                                        value={modal.form.data.assign_to}
                                        onChange={(event) =>
                                            modal.form.setData('assign_to', event.target.value)
                                        }
                                        className="crm-form-select"
                                    >
                                        <option value="">Select legal officer</option>
                                        {legalOfficers.map((officer) => (
                                            <option key={officer.id} value={officer.id}>
                                                {officer.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="flex items-end">
                                    <label className="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="checkbox"
                                            checked={modal.form.data.stamp_duty}
                                            onChange={(event) =>
                                                modal.form.setData(
                                                    'stamp_duty',
                                                    event.target.checked,
                                                )
                                            }
                                            className="rounded border-gray-300 text-indigo-600"
                                        />
                                        Stamp Duty
                                    </label>
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

Legals.layout = (page: ReactNode) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Legal
                    </h2>
                </div>
            </div>
        }
    >
        {page}
    </CrmLayout>
);
