
import { Head, useForm } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { CrmFilterSearch } from '@/components/crm/filter-search';
import { CrmFilterTabs } from '@/components/crm/filter-tabs';
import { LoanDetailModal } from '@/components/crm/loan-detail-modal';
import { CrmPagination, type PaginationLink } from '@/components/crm/pagination';
import { useCrmFilters } from '@/hooks/use-crm-filters';
import CrmLayout from '@/layouts/crm-layout';

type BankApprovalRate = {
    bank: string;
    approved_count: number;
    submitted_count: number;
    approval_rate: number;
};

type ApprovalRow = {
    deal_id?: number | null;
    deal_code?: string | null;
    project_name?: string | null;
    client_name?: string | null;
    loan_officer_name?: string | null;
    loan_id?: string | null;
    approved_bank?: string | null;
    banker_contact?: string | null;
    applied_amount?: number | null;
    approved_amount?: number | null;
    approval_deviation_percentage?: number | null;
    interest_rate?: number | null;
    lock_in_period?: string | null;
    mrta_mlta?: string | null;
    special_conditions?: string | null;
    has_record?: boolean;
};

type PaginatedSubmissions = {
    data: ApprovalRow[];
    links: PaginationLink[];
};

type ApprovalProps = {
    approvedSubmissions: PaginatedSubmissions;
    bankOptions: string[];
    canManageLoanRecords: boolean;
    bankApprovalRates: BankApprovalRate[];
};

type ApprovalFormData = {
    loan_id: string;
    approved_bank: string;
    applied_amount: string;
    approved_amount: string;
    interest_rate: string;
    lock_in_period: string;
    mrta_mlta: string;
    special_conditions: string;
};

export default function ApprovalAnalysis({
    approvedSubmissions,
    bankOptions,
    canManageLoanRecords,
    bankApprovalRates,
}: ApprovalProps) {
    const [bankFilter, setBankFilter] = useState(() =>
        typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search).get('bank') ?? ''
            : '',
    );
    const {
        searchTerm,
        setSearchTerm,
        statusFilter: completionFilter,
        submitSearch,
        resetSearch,
        applyStatus,
    } = useCrmFilters({
        baseUrl: '/loans/approval-analysis',
        statusKey: 'completion',
        extra: { bank: bankFilter },
    });

    const [detailDealId, setDetailDealId] = useState<number | null>(null);
    const [detailLoanId, setDetailLoanId] = useState<string | number | null>(null);
    const [editOpen, setEditOpen] = useState(false);
    const [selectedRow, setSelectedRow] = useState<ApprovalRow | null>(null);

    const form = useForm<ApprovalFormData>({
        loan_id: '',
        approved_bank: '',
        applied_amount: '',
        approved_amount: '',
        interest_rate: '',
        lock_in_period: '',
        mrta_mlta: '',
        special_conditions: '',
    });

    const openEdit = (row: ApprovalRow) => {
        setSelectedRow(row);
        form.setData({
            loan_id: row.loan_id ?? '',
            approved_bank: row.approved_bank ?? '',
            applied_amount:
                row.applied_amount != null ? String(row.applied_amount) : '',
            approved_amount:
                row.approved_amount != null ? String(row.approved_amount) : '',
            interest_rate: row.interest_rate != null ? String(row.interest_rate) : '',
            lock_in_period: row.lock_in_period ?? '',
            mrta_mlta: row.mrta_mlta ?? '',
            special_conditions: row.special_conditions ?? '',
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
        const method = selectedRow.has_record ? 'put' : 'post';
        const url = `/loans/approval-analysis/${selectedRow.deal_id}`;
        if (method === 'put') {
            form.put(url, {
                preserveScroll: true,
                onSuccess: () => closeEdit(),
            });
        } else {
            form.post(url, {
                preserveScroll: true,
                onSuccess: () => closeEdit(),
            });
        }
    };

    const rows = approvedSubmissions?.data ?? [];
    const sortStart = canManageLoanRecords ? 1 : 0;

    return (
        <>
            <Head title="Loans / Approval Analysis" />

            <div className="grid grid-cols-3 gap-3 md:grid-cols-4 lg:grid-cols-7 mb-4">
                {bankApprovalRates?.map((rate) => (
                    <article key={rate.bank} className="crm-kpi">
                        <p className="crm-kpi-label">{rate.bank ?? '-'}</p>
                        <p className="mt-2 text-xl font-semibold text-slate-900">
                            {Number(rate.approval_rate ?? 0).toFixed(2)}%
                        </p>
                        <p className="mt-1 text-xs text-slate-500">
                            {Number(rate.approved_count ?? 0)} /{' '}
                            {Number(rate.submitted_count ?? 0)} approved
                        </p>
                    </article>
                ))}
            </div>
            <div className="space-y-6">
                <div className="crm-card">
                    <div className="crm-card-body">
                        <div className="crm-filter-block">
                            <div className="grid grid-cols-1 sm:grid-cols-2 justify-between gap-3">
                                <CrmFilterSearch
                                    value={searchTerm}
                                    onChange={setSearchTerm}
                                    onSubmit={submitSearch}
                                    onClear={resetSearch}
                                    placeholder="Search project, client or banker..."
                                    className="crm-filter-search-row"
                                />
                                <div className="grid grid-cols-2">
                                    <div></div>
                                    <select
                                        className="crm-form-input"
                                        value={bankFilter}
                                        onChange={(event) => {
                                            const nextBank = event.target.value;
                                            setBankFilter(nextBank);
                                            submitSearch(undefined, {
                                                bank: nextBank || '',
                                            });
                                        }}
                                    >
                                        <option value="">All Banks</option>
                                        {bankOptions.map((bank) => (
                                            <option key={bank} value={bank}>
                                                {bank}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <CrmFilterTabs
                                items={[
                                    {
                                        label: 'All',
                                        value: '',
                                        active: completionFilter === '',
                                        variant: 'all',
                                        onClick: () => {
                                            applyStatus('', { bank: bankFilter });
                                        },
                                    },
                                    {
                                        label: 'New',
                                        value: 'new',
                                        active: completionFilter === 'new',
                                        variant: 'stage',
                                        onClick: () => {
                                            applyStatus('new', { bank: bankFilter });
                                        },
                                    },
                                    {
                                        label: 'Completed',
                                        value: 'completed',
                                        active: completionFilter === 'completed',
                                        variant: 'stage',
                                        onClick: () => {
                                            applyStatus('completed', {
                                                bank: bankFilter,
                                            });
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
                                        <th data-sort-index={sortStart} className="w-[20%]">
                                            <span className="crm-sort-btn">
                                                Project <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 1}>
                                            <span className="crm-sort-btn">
                                                Client <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 2}>
                                            <span className="crm-sort-btn">
                                                Loan Officer <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 3}>
                                            <span className="crm-sort-btn">
                                                Approved Bank <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 4}>
                                            <span className="crm-sort-btn">
                                                Banker <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 5} data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Applied Amount (RM)<span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 6} data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Approved Amount (RM)<span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 11} data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Approval Deviation (%) <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 7} data-sort-type="number">
                                            <span className="crm-sort-btn">
                                                Interest Rate <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 8}>
                                            <span className="crm-sort-btn">
                                                Lock-in Period <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 9}>
                                            <span className="crm-sort-btn">
                                                MRTA / MLTA <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={sortStart + 10} className="w-[8%]">
                                            <span className="crm-sort-btn">
                                                Special Conditions <span data-sort-indicator></span>
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
                                                <td data-sort-value={row.approved_bank?.toLowerCase() ?? ''}>
                                                    <b>{row.approved_bank ?? '-'}</b>
                                                </td>
                                                <td data-sort-value={row.banker_contact?.toLowerCase() ?? ''}>
                                                    {row.banker_contact ?? '-'}
                                                </td>
                                                <td data-sort-value={row.applied_amount ?? 0}>
                                                    {row.applied_amount != null
                                                        ? row.applied_amount.toLocaleString('en-US', {
                                                              minimumFractionDigits: 2,
                                                              maximumFractionDigits: 2,
                                                          })
                                                        : '-'}
                                                </td>
                                                <td data-sort-value={row.approved_amount ?? 0}>
                                                    {row.approved_amount != null
                                                        ? row.approved_amount.toLocaleString('en-US', {
                                                              minimumFractionDigits: 2,
                                                              maximumFractionDigits: 2,
                                                          })
                                                        : '-'}
                                                </td>
                                                <td data-sort-value={row.approval_deviation_percentage ?? 0}>
                                                    {row.approval_deviation_percentage ?? '-'}
                                                </td>
                                                <td data-sort-value={row.interest_rate ?? 0}>
                                                    {row.interest_rate ?? '-'}
                                                </td>
                                                <td data-sort-value={row.lock_in_period?.toLowerCase() ?? ''}>
                                                    {row.lock_in_period ?? '-'}
                                                </td>
                                                <td data-sort-value={row.mrta_mlta?.toLowerCase() ?? ''}>
                                                    {row.mrta_mlta ?? '-'}
                                                </td>
                                                <td data-sort-value={row.special_conditions?.toLowerCase() ?? ''}>
                                                    {row.special_conditions ?? '-'}
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={canManageLoanRecords ? 13 : 12}
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
                                    ? 'Edit Approval Analysis'
                                    : 'Add Approval Analysis'}
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
                                    <label className="crm-form-label">Approved Bank</label>
                                    <select
                                        name="approved_bank"
                                        value={form.data.approved_bank}
                                        onChange={(event) =>
                                            form.setData('approved_bank', event.target.value)
                                        }
                                        className="crm-form-select"
                                        required
                                    >
                                        <option value="">-</option>
                                        {bankOptions.map((bank) => (
                                            <option key={bank} value={bank}>
                                                {bank}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="crm-form-label">Applied Amount</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        name="applied_amount"
                                        value={form.data.applied_amount}
                                        onChange={(event) =>
                                            form.setData('applied_amount', event.target.value)
                                        }
                                        className="crm-form-input"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Approved Amount</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        name="approved_amount"
                                        value={form.data.approved_amount}
                                        onChange={(event) =>
                                            form.setData('approved_amount', event.target.value)
                                        }
                                        className="crm-form-input"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Interest Rate</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        name="interest_rate"
                                        value={form.data.interest_rate}
                                        onChange={(event) =>
                                            form.setData('interest_rate', event.target.value)
                                        }
                                        className="crm-form-input"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Lock-in Period</label>
                                    <input
                                        type="text"
                                        name="lock_in_period"
                                        value={form.data.lock_in_period}
                                        onChange={(event) =>
                                            form.setData('lock_in_period', event.target.value)
                                        }
                                        className="crm-form-input"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">MRTA / MLTA</label>
                                    <input
                                        type="text"
                                        name="mrta_mlta"
                                        value={form.data.mrta_mlta}
                                        onChange={(event) =>
                                            form.setData('mrta_mlta', event.target.value)
                                        }
                                        className="crm-form-input"
                                        required
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <label className="crm-form-label">Special Conditions</label>
                                    <input
                                        type="text"
                                        name="special_conditions"
                                        value={form.data.special_conditions}
                                        onChange={(event) =>
                                            form.setData(
                                                'special_conditions',
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

ApprovalAnalysis.layout = (page: ReactNode) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Loans / Approval Analysis
                    </h2>
                </div>
            </div>
        }
    >
        {page}
    </CrmLayout>
);
