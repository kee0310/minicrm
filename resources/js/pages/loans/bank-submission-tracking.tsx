
import { Head, useForm } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { CrmFilterSearch } from '@/components/crm/filter-search';
import { CrmFilterTabs } from '@/components/crm/filter-tabs';
import { LoanDetailModal } from '@/components/crm/loan-detail-modal';
import { useCrmFilters } from '@/hooks/use-crm-filters';
import CrmLayout from '@/layouts/crm-layout';

type SubmissionRow = {
    deal_id: number;
    deal_code?: string | null;
    project_name?: string | null;
    client_name?: string | null;
    loan_officer_name?: string | null;
    submission?: {
        loan_id?: string | null;
        bank_name?: string | null;
        banker_contact?: string | null;
        submission_date?: string | null;
        document_completeness_score?: number | null;
        approval_status?: string | null;
        expected_approval_date?: string | null;
        file_completeness_percentage?: number | null;
    } | null;
    flags: {
        has_submission: boolean;
        is_rejected: boolean;
        show_create: boolean;
        show_action: boolean;
        hide_edit: boolean;
    };
};

type BankSubmissionProps = {
    deals: SubmissionRow[];
    bankOptions: string[];
    statusOptions: string[];
    canManageLoanRecords: boolean;
    summary: Record<string, number>;
};

type BankFormData = {
    deal_id: string;
    loan_id: string;
    bank_name: string;
    banker_contact: string;
    submission_date: string;
    document_completeness_score: string;
    approval_status: string;
    expected_approval_date: string;
    file_completeness_percentage: string;
};

const statusBadgeClass = (status?: string | null) => {
    switch (status) {
        case 'Prepared':
            return 'bg-gray-200 text-gray-700';
        case 'Submitted':
            return 'bg-blue-100 text-blue-700';
        case 'In Review':
            return 'bg-amber-100 text-amber-700';
        case 'Approved':
            return 'bg-green-100 text-green-700';
        case 'Rejected':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-200 text-gray-600';
    }
};

export default function BankSubmissionTracking({
    deals,
    bankOptions,
    statusOptions,
    canManageLoanRecords,
    summary,
}: BankSubmissionProps) {
    const [bankFilter, setBankFilter] = useState(() =>
        typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search).get('bank') ?? ''
            : '',
    );
    const {
        searchTerm,
        setSearchTerm,
        statusFilter,
        submitSearch,
        resetSearch,
        applyStatus,
    } = useCrmFilters({
        baseUrl: '/loans/bank-submission-tracking',
        statusKey: 'status',
        extra: { bank: bankFilter },
    });

    const [detailDealId, setDetailDealId] = useState<number | null>(null);
    const [detailLoanId, setDetailLoanId] = useState<string | number | null>(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [mode, setMode] = useState<'create' | 'edit'>('create');

    const form = useForm<BankFormData>({
        deal_id: '',
        loan_id: '',
        bank_name: '',
        banker_contact: '',
        submission_date: '',
        document_completeness_score: '',
        approval_status: statusOptions[0] ?? 'Prepared',
        expected_approval_date: '',
        file_completeness_percentage: '',
    });

    const openCreate = (deal: SubmissionRow) => {
        setMode('create');
        form.setData({
            deal_id: String(deal.deal_id ?? ''),
            loan_id: '',
            bank_name: '',
            banker_contact: '',
            submission_date: '',
            document_completeness_score: '',
            approval_status: 'Prepared',
            expected_approval_date: '',
            file_completeness_percentage: '',
        });
        setModalOpen(true);
    };

    const openEdit = (deal: SubmissionRow) => {
        const submission = deal.submission;
        if (!submission) {
            return;
        }
        setMode('edit');
        form.setData({
            deal_id: String(deal.deal_id ?? ''),
            loan_id: submission.loan_id ?? '',
            bank_name: submission.bank_name ?? '',
            banker_contact: submission.banker_contact ?? '',
            submission_date: submission.submission_date ?? '',
            document_completeness_score:
                submission.document_completeness_score != null
                    ? String(submission.document_completeness_score)
                    : '',
            approval_status: submission.approval_status ?? 'Prepared',
            expected_approval_date: submission.expected_approval_date ?? '',
            file_completeness_percentage:
                submission.file_completeness_percentage != null
                    ? String(submission.file_completeness_percentage)
                    : '',
        });
        setModalOpen(true);
    };

    const closeModal = () => {
        setModalOpen(false);
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        if (mode === 'edit') {
            form.put(`/loans/bank-submission-tracking/submissions/${form.data.loan_id}`, {
                preserveScroll: true,
                onSuccess: () => closeModal(),
            });
            return;
        }
        form.post('/loans/bank-submission-tracking', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    };

    return (
        <>
            <Head title="Loans / Bank Submission Tracking" />

            <div className="space-y-6">
                <section className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <article className="crm-kpi">
                        <p className="crm-kpi-label">New</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">
                            {(summary?.new ?? 0).toLocaleString('en-US')}
                        </p>
                    </article>
                    {statusOptions.map((status) => {
                        const key = status.toLowerCase().replaceAll(' ', '_');
                        const value = Number(summary?.[key] ?? 0);
                        const isRejected = status.toLowerCase() === 'rejected';
                        const isApproved = status.toLowerCase() === 'approved';
                        return (
                            <article key={status} className="crm-kpi">
                                <p className="crm-kpi-label">{status}</p>
                                <p
                                    className={`mt-2 text-2xl font-semibold ${
                                        isRejected
                                            ? 'text-red-600'
                                            : isApproved
                                              ? 'text-green-600'
                                              : 'text-slate-900'
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
                                        active: statusFilter === '',
                                        variant: 'all',
                                        onClick: () => {
                                            applyStatus('', { bank: bankFilter });
                                        },
                                    },
                                    {
                                        label: 'New',
                                        value: 'No Submission',
                                        active: statusFilter === 'No Submission',
                                        variant: 'stage',
                                        onClick: () => {
                                            applyStatus('No Submission', {
                                                bank: bankFilter,
                                            });
                                        },
                                    },
                                    ...statusOptions.map((status) => ({
                                        label: status,
                                        value: status,
                                        active: statusFilter === status,
                                        variant: 'stage' as const,
                                        onClick: () => {
                                            applyStatus(status, { bank: bankFilter });
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
                                        <th data-sort-index={canManageLoanRecords ? 1 : 0}>
                                            <span className="crm-sort-btn">
                                                Project <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={canManageLoanRecords ? 2 : 1}>
                                            <span className="crm-sort-btn">
                                                Client <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={canManageLoanRecords ? 3 : 2}>
                                            <span className="crm-sort-btn">
                                                Loan Officer <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={canManageLoanRecords ? 4 : 3}>
                                            <span className="crm-sort-btn">
                                                Bank <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={canManageLoanRecords ? 5 : 4}>
                                            <span className="crm-sort-btn">
                                                Banker Contact <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th
                                            data-sort-index={canManageLoanRecords ? 6 : 5}
                                            data-sort-type="date"
                                            className="w-[10%]"
                                        >
                                            <span className="crm-sort-btn">
                                                Submission Date <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th
                                            data-sort-index={canManageLoanRecords ? 7 : 6}
                                            data-sort-type="number"
                                            className="w-[5%]"
                                        >
                                            <span className="crm-sort-btn">
                                                Doc Score <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th
                                            data-sort-index={canManageLoanRecords ? 8 : 7}
                                            data-sort-type="number"
                                            className="w-[5%]"
                                        >
                                            <span className="crm-sort-btn">
                                                File Completeness (%) <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th data-sort-index={canManageLoanRecords ? 9 : 8} className="w-[5%]">
                                            <span className="crm-sort-btn">
                                                Approval Status <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                        <th
                                            data-sort-index={canManageLoanRecords ? 10 : 9}
                                            data-sort-type="date"
                                            className="w-[10%]"
                                        >
                                            <span className="crm-sort-btn">
                                                Expected Approval <span data-sort-indicator></span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {deals.length ? (
                                        deals.map((row, index) => {
                                            const submission = row.submission;
                                            const statusClass = statusBadgeClass(
                                                submission?.approval_status,
                                            );
                                            return (
                                                <tr key={`${row.deal_id}-${index}`}>
                                                    {canManageLoanRecords && (
                                                        <td>
                                                            {row.flags.show_action && row.flags.show_create && (
                                                                <button
                                                                    type="button"
                                                                    className={
                                                                        row.flags.is_rejected
                                                                            ? 'crm-action-btn-danger'
                                                                            : 'crm-action-btn'
                                                                    }
                                                                    title={
                                                                        row.flags.is_rejected
                                                                            ? 'Create new case'
                                                                            : 'Create'
                                                                    }
                                                                    onClick={() => openCreate(row)}
                                                                >
                                                                    <i className="fa-solid fa-plus"></i>
                                                                </button>
                                                            )}
                                                            {row.flags.show_action &&
                                                                !row.flags.show_create &&
                                                                !row.flags.hide_edit && (
                                                                    <button
                                                                        type="button"
                                                                        className="crm-action-btn"
                                                                        title="Edit"
                                                                        onClick={() => openEdit(row)}
                                                                    >
                                                                        <i className="fa-solid fa-pen-to-square"></i>
                                                                    </button>
                                                                )}
                                                        </td>
                                                    )}
                                                    <td data-sort-value={row.deal_code?.toLowerCase() ?? ''}>
                                                        <button
                                                            type="button"
                                                            className="truncate text-indigo-600 hover:underline"
                                                            onClick={() => {
                                                                setDetailDealId(row.deal_id);
                                                                setDetailLoanId(submission?.loan_id ?? null);
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
                                                    <td data-sort-value={submission?.bank_name?.toLowerCase() ?? ''}>
                                                        <b>{submission?.bank_name ?? '-'}</b>
                                                    </td>
                                                    <td data-sort-value={submission?.banker_contact?.toLowerCase() ?? ''}>
                                                        {submission?.banker_contact ?? '-'}
                                                    </td>
                                                    <td data-sort-value={submission?.submission_date ?? ''} className="crm-table-date">
                                                        {submission?.submission_date ?? '-'}
                                                    </td>
                                                    <td data-sort-value={submission?.document_completeness_score ?? 0}>
                                                        {submission?.document_completeness_score ?? '-'}
                                                    </td>
                                                    <td
                                                        data-sort-value={submission?.file_completeness_percentage ?? 0}
                                                        className={
                                                            submission?.file_completeness_percentage != null &&
                                                            submission.file_completeness_percentage < 80
                                                                ? '!text-red-600'
                                                                : ''
                                                        }
                                                    >
                                                        {submission?.file_completeness_percentage == null
                                                            ? '-'
                                                            : `${submission.file_completeness_percentage}%`}
                                                    </td>
                                                    <td data-sort-value={submission?.approval_status?.toLowerCase() ?? 'no submission'}>
                                                        {submission ? (
                                                            <span
                                                                className={`inline-flex text-xs items-center px-2.5 py-1 rounded-full font-semibold ${statusClass}`}
                                                            >
                                                                {submission.approval_status}
                                                            </span>
                                                        ) : (
                                                            '-'
                                                        )}
                                                    </td>
                                                    <td
                                                        data-sort-value={submission?.expected_approval_date ?? ''}
                                                        className="crm-table-date"
                                                    >
                                                        {submission?.expected_approval_date ?? '-'}
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
                                                No deals in Booking/SPA Signed/Loan stages.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            {modalOpen && canManageLoanRecords && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                    onClick={(event) => {
                        if (event.currentTarget === event.target) {
                            closeModal();
                        }
                    }}
                >
                    <div className="crm-modal-panel">
                        <div className="crm-modal-header">
                            <h4 className="crm-modal-title">
                                {mode === 'edit' ? 'Edit Bank Submission' : 'Create Case'}
                            </h4>
                            <button
                                type="button"
                                className="text-gray-500 hover:text-gray-700"
                                onClick={closeModal}
                            >
                                X
                            </button>
                        </div>
                        <form onSubmit={submit} data-preserve-list-state>
                            <input type="hidden" name="deal_id" value={form.data.deal_id} />
                            <div className="crm-modal-grid">
                                <div>
                                    <label className="crm-form-label">Bank Name</label>
                                    <select
                                        name="bank_name"
                                        value={form.data.bank_name}
                                        onChange={(event) =>
                                            form.setData('bank_name', event.target.value)
                                        }
                                        className="crm-form-select"
                                        required
                                    >
                                        <option value="">Select</option>
                                        {bankOptions.map((bank) => (
                                            <option key={bank} value={bank}>
                                                {bank}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="crm-form-label">Banker Contact</label>
                                    <input
                                        type="text"
                                        name="banker_contact"
                                        value={form.data.banker_contact}
                                        onChange={(event) =>
                                            form.setData('banker_contact', event.target.value)
                                        }
                                        className="crm-form-input"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Submission Date</label>
                                    <input
                                        type="date"
                                        name="submission_date"
                                        value={mode === 'edit' ? form.data.submission_date : ''}
                                        onChange={(event) =>
                                            form.setData('submission_date', event.target.value)
                                        }
                                        className="crm-form-input"
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Doc Score (1-5)</label>
                                    <input
                                        type="number"
                                        name="document_completeness_score"
                                        min={1}
                                        max={5}
                                        value={form.data.document_completeness_score}
                                        onChange={(event) =>
                                            form.setData(
                                                'document_completeness_score',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">Approval Status</label>
                                    <select
                                        name="approval_status"
                                        value={form.data.approval_status}
                                        onChange={(event) =>
                                            form.setData('approval_status', event.target.value)
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
                                <div>
                                    <label className="crm-form-label">Expected Approval Date</label>
                                    <input
                                        type="date"
                                        name="expected_approval_date"
                                        value={form.data.expected_approval_date}
                                        onChange={(event) =>
                                            form.setData(
                                                'expected_approval_date',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="crm-form-label">File Completeness (%)</label>
                                    <input
                                        type="number"
                                        name="file_completeness_percentage"
                                        min={0}
                                        max={100}
                                        value={form.data.file_completeness_percentage}
                                        onChange={(event) =>
                                            form.setData(
                                                'file_completeness_percentage',
                                                event.target.value,
                                            )
                                        }
                                        className="crm-form-input"
                                        required
                                    />
                                </div>
                            </div>
                            <div className="crm-modal-footer">
                                <button
                                    type="button"
                                    onClick={closeModal}
                                    className="crm-btn-secondary"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="crm-btn-primary"
                                    disabled={form.processing}
                                >
                                    {mode === 'edit' ? 'Save' : 'Create'}
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

BankSubmissionTracking.layout = (page: ReactNode) => (
    <CrmLayout
        header={
            <div className="flex items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Loans / Bank Submission Tracking
                    </h2>
                </div>
            </div>
        }
    >
        {page}
    </CrmLayout>
);
