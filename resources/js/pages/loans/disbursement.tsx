
import { Head, useForm } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { CrmFilterSearch } from '@/components/crm/filter-search';
import { CrmFilterTabs } from '@/components/crm/filter-tabs';
import { LoanDetailModal } from '@/components/crm/loan-detail-modal';
import { CrmPagination, type PaginationLink } from '@/components/crm/pagination';
import { useCrmFilters } from '@/hooks/use-crm-filters';
import CrmLayout from '@/layouts/crm-layout';

type DisbursementRow = {
    deal_id?: number | null;
    deal_code?: string | null;
    project_name?: string | null;
    client_name?: string | null;
    loan_officer_name?: string | null;
    loan_id?: string | null;
    first_disbursement_date?: string | null;
    full_disbursement_date?: string | null;
    spa_completion_date?: string | null;
    client_notification_date?: string | null;
    has_record?: boolean;
};

type PaginatedSubmissions = {
    data: DisbursementRow[];
    links: PaginationLink[];
};

type DisbursementProps = {
    approvedSubmissions: PaginatedSubmissions;
    canManageLoanRecords: boolean;
};

type DisbursementFormData = {
    loan_id: string;
    first_disbursement_date: string;
    full_disbursement_date: string;
    spa_completion_date: string;
    client_notification_date: string;
};

const formatDisplayDate = (value?: string | null) => {
    if (!value) {
        return '-';
    }
    const parsed = new Date(`${value}T00:00:00`);
    if (Number.isNaN(parsed.getTime())) {
        return value;
    }
    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(parsed);
};

export default function Disbursement({
    approvedSubmissions,
    canManageLoanRecords,
}: DisbursementProps) {
    const {
        searchTerm,
        setSearchTerm,
        statusFilter: completionFilter,
        submitSearch,
        resetSearch,
        applyStatus,
    } = useCrmFilters({
        baseUrl: '/loans/disbursement',
        statusKey: 'completion',
    });

    const [detailDealId, setDetailDealId] = useState<number | null>(null);
    const [detailLoanId, setDetailLoanId] = useState<string | number | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [selectedRow, setSelectedRow] = useState<DisbursementRow | null>(null);

    const form = useForm<DisbursementFormData>({
        loan_id: '',
        first_disbursement_date: '',
        full_disbursement_date: '',
        spa_completion_date: '',
        client_notification_date: '',
    });

    const openEdit = (row: DisbursementRow) => {
        setSelectedRow(row);
        form.setData({
            loan_id: row.loan_id ?? '',
            first_disbursement_date: row.first_disbursement_date ?? '',
            full_disbursement_date: row.full_disbursement_date ?? '',
            spa_completion_date: row.spa_completion_date ?? '',
            client_notification_date: row.client_notification_date ?? '',
        });
        setEditOpen(true);
    };

    const closeEdit = () => {
        setEditOpen(false);
        setSelectedRow(null);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        if (!selectedRow || !selectedRow.deal_id) {
            return;
        }
        form.put(`/loans/disbursement/${selectedRow.deal_id}`, {
            preserveScroll: true,
            onSuccess: () => closeEdit(),
        });
    };

    const rows = approvedSubmissions?.data ?? [];
    const sortStart = canManageLoanRecords ? 1 : 0;

    return (
        <>
            <Head title="Loans / Disbursement" />

            <div className="space-y-6">
                <div className="crm-card">
                    <div className="crm-card-body">
                        <div className="crm-filter-block">
                            <CrmFilterSearch
                                value={searchTerm}
                                onChange={setSearchTerm}
                                onSubmit={submitSearch}
                                onClear={resetSearch}
                                placeholder="Search project or client..."
                                className="crm-filter-search-row"
                            />
                            <CrmFilterTabs
                                items={[
                                    {
                                        label: 'All',
                                        value: '',
                                        active: completionFilter === '',
                                        variant: 'all',
                                        onClick: () => {
                                            applyStatus('');
                                        },
                                    },
                                    {
                                        label: 'New',
                                        value: 'new',
                                        active: completionFilter === 'new',
                                        variant: 'stage',
                                        onClick: () => {
                                            applyStatus('new');
                                        },
                                    },
                                    {
                                        label: 'Completed',
                                        value: 'completed',
                                        active: completionFilter === 'completed',
                                        variant: 'stage',
                                        onClick: () => {
                                            applyStatus('completed');
                                        },
                                    },
                                ]}
                            />
                        </div>

                        <div className="crm-table-wrap">
                            <table className="crm-table crm-table-center" data-sortable-table="true">
                                <thead>
                                    <tr>
                                        {canManageLoanRecords && <th></th>}
                                        <th data-sort-index={sortStart} className="w-[15%]">
                                            <span className="crm-sort-btn">
                                                Project <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 1} className="w-[12%]">
                                            <span className="crm-sort-btn">
                                                Client <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 2}>
                                            <span className="crm-sort-btn">
                                                Loan Officer <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 3} data-sort-type="date">
                                            <span className="crm-sort-btn">
                                                First Disbursement Date <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 4} data-sort-type="date">
                                            <span className="crm-sort-btn">
                                                Full Disbursement Date <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 5} data-sort-type="date">
                                            <span className="crm-sort-btn">
                                                SPA Completion Date <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 6} data-sort-type="date">
                                            <span className="crm-sort-btn">
                                                Client Notification Date <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {rows.length ? (
                                        rows.map((row) => (
                                            <tr key={`${row.loan_id ?? 'loan'}-${row.deal_id ?? ''}`}>
                                                {canManageLoanRecords && (
                                                    <td>
                                                        <button
                                                            type="button"
                                                            className="crm-action-btn"
                                                            onClick={() => openEdit(row)}
                                                        >
                                                            <i
                                                                className={`fa-solid ${
                                                                    row.has_record
                                                                        ? 'fa-pen-to-square'
                                                                        : 'fa-plus'
                                                                }`}
                                                            ></i>
                                                        </button>
                                                    </td>
                                                )}
                                                <td data-sort-value={row.deal_code?.toLowerCase() ?? ''}>
                                                    <button
                                                        type="button"
                                                        className="truncate text-indigo-600 hover:underline"
                                                        onClick={() => {
                                                            if (row.deal_id) {
                                                                setDetailDealId(row.deal_id);
                                                                setDetailLoanId(row.loan_id ?? null);
                                                            }
                                                        }}
                                                    >
                                                        {row.deal_code ?? '-'}:
                                                    </button>
                                                    <br />
                                                    {row.project_name ?? '-'}
                                                </td>
                                                <td data-sort-value={row.client_name?.toLowerCase() ?? ''}>
                                                    {row.client_name ?? '-'}
                                                </td>
                                                <td data-sort-value={row.loan_officer_name?.toLowerCase() ?? ''}>
                                                    {row.loan_officer_name ?? '-'}
                                                </td>
                                                <td
                                                    data-sort-value={row.first_disbursement_date ?? ''}
                                                    className="crm-table-date"
                                                >
                                                    {formatDisplayDate(
                                                        row.first_disbursement_date,
                                                    )}
                                                </td>
                                                <td
                                                    data-sort-value={row.full_disbursement_date ?? ''}
                                                    className="crm-table-date"
                                                >
                                                    {formatDisplayDate(row.full_disbursement_date)}
                                                </td>
                                                <td
                                                    data-sort-value={row.spa_completion_date ?? ''}
                                                    className="crm-table-date"
                                                >
                                                    {formatDisplayDate(row.spa_completion_date)}
                                                </td>
                                                <td
                                                    data-sort-value={row.client_notification_date ?? ''}
                                                    className="crm-table-date"
                                                >
                                                    {formatDisplayDate(
                                                        row.client_notification_date,
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={canManageLoanRecords ? 8 : 7}
                                                className="crm-table-empty"
                                            >
                                                No approved loans found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4">
                            <CrmPagination links={approvedSubmissions?.links ?? []} />
                        </div>
                    </div>
                </div>
            </div>

            {editOpen && selectedRow && canManageLoanRecords && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                    onClick={(event) => {
                        if (event.currentTarget === event.target) {
                            closeEdit();
                        }
                    }}
                >
                    <div className="crm-modal-panel">
                        <div className="crm-modal-header">
                            <h4 className="crm-modal-title">
                                {selectedRow.has_record
                                    ? 'Edit Disbursement'
                                    : 'Add Disbursement'}
                            </h4>
                            <button
                                type="button"
                                className="text-gray-500 hover:text-gray-700"
                                onClick={closeEdit}
                            >
                                X
                            </button>
                        </div>

                        <form onSubmit={submit} data-preserve-list-state>
                            <input type="hidden" name="loan_id" value={form.data.loan_id} />
                            <div className="crm-modal-grid">
                                <div>
                                    <label className="crm-form-label">First Disbursement Date</label>
                                    <input
                                        type="date"
                                        name="first_disbursement_date"
                                        value={form.data.first_disbursement_date}
                                        onChange={(event) =>
                                            form.setData(
                                                'first_disbursement_date',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Full Disbursement Date</label>
                                    <input
                                        type="date"
                                        name="full_disbursement_date"
                                        value={form.data.full_disbursement_date}
                                        onChange={(event) =>
                                            form.setData(
                                                'full_disbursement_date',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">SPA Completion Date</label>
                                    <input
                                        type="date"
                                        name="spa_completion_date"
                                        value={form.data.spa_completion_date}
                                        onChange={(event) =>
                                            form.setData(
                                                'spa_completion_date',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Client Notification Date</label>
                                    <input
                                        type="date"
                                        name="client_notification_date"
                                        value={form.data.client_notification_date}
                                        onChange={(event) =>
                                            form.setData(
                                                'client_notification_date',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                            </div>
                            <div className="crm-modal-footer">
                                <button
                                    type="button"
                                    onClick={closeEdit}
                                    className="crm-btn-secondary"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="crm-btn-primary"
                                    disabled={form.processing}
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
                loanId={detailLoanId}
                onClose={() => {
                    setDetailDealId(null);
                    setDetailLoanId(null);
                }}
            />
        </>
    );
}

Disbursement.layout = (page: ReactNode) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Loans / Disbursement
                    </h2>
                </div>
            </div>
        }
    >
        {page}
    </CrmLayout>
);
